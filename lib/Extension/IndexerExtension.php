<?php

namespace Phpactor\Indexer\Extension;

use Phpactor\AmpFsWatch\Watcher;
use Phpactor\AmpFsWatch\WatcherConfig;
use Phpactor\AmpFsWatch\Watcher\BufferedWatcher\BufferedWatcher;
use Phpactor\AmpFsWatch\Watcher\Fallback\FallbackWatcher;
use Phpactor\AmpFsWatch\Watcher\Find\FindWatcher;
use Phpactor\AmpFsWatch\Watcher\FsWatch\FsWatchWatcher;
use Phpactor\AmpFsWatch\Watcher\Inotify\InotifyWatcher;
use Phpactor\AmpFsWatch\Watcher\Null\NullWatcher;
use Phpactor\AmpFsWatch\Watcher\PatternMatching\PatternMatchingWatcher;
use Phpactor\AmpFsWatch\Watcher\PhpPollWatcher\PhpPollWatcher;
use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\ReferenceFinder\ReferenceFinderExtension;
use Phpactor\Extension\Rpc\RpcExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\FilePathResolver\PathResolver;
use Phpactor\Filesystem\Adapter\Simple\SimpleFileListProvider;
use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\Indexer\Adapter\Php\FileSearchIndex;
use Phpactor\Indexer\Adapter\ReferenceFinder\IndexedNameSearcher;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexBuilder;
use Phpactor\Indexer\Adapter\Worse\IndexerClassSourceLocator;
use Phpactor\Indexer\Adapter\Worse\IndexerFunctionSourceLocator;
use Phpactor\Indexer\Adapter\ReferenceFinder\IndexedReferenceFinder;
use Phpactor\Indexer\Adapter\Worse\WorseRecordReferenceEnhancer;
use Phpactor\Indexer\IndexAgent;
use Phpactor\Indexer\IndexAgentBuilder;
use Phpactor\Indexer\Model\IndexAccess;
use Phpactor\Indexer\Model\Matcher\ClassShortNameMatcher;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Indexer\Model\SearchIndex\FilteredSearchIndex;
use Phpactor\MapResolver\Resolver;
use Phpactor\Indexer\Adapter\Filesystem\FilesystemFileListProvider;
use Phpactor\Indexer\Adapter\Php\Serialized\FileRepository;
use Phpactor\Indexer\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\Indexer\Adapter\ReferenceFinder\IndexedImplementationFinder;
use Phpactor\Indexer\Extension\Command\IndexQueryCommand;
use Phpactor\Indexer\Extension\Command\IndexBuildCommand;
use Phpactor\Indexer\Extension\Rpc\IndexHandler;
use Phpactor\Indexer\Model\FileListProvider;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Model\QueryClient;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\TextDocument\TextDocumentUri;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\ReflectorBuilder;
use RuntimeException;
use Webmozart\PathUtil\Path;

class IndexerExtension implements Extension
{
    const PARAM_INDEX_PATH = 'indexer.index_path';
    const PARAM_INDEXER_POLL_TIME = 'indexer.poll_time';
    const PARAM_ENABLED_WATCHERS = 'indexer.enabled_watchers';
    const PARAM_INCLUDE_PATTERNS = 'indexer.include_patterns';
    const PARAM_EXCLUDE_PATTERNS = 'indexer.exclude_patterns';
    const PARAM_INDEXER_BUFFER_TIME = 'indexer.buffer_time';

    const TAG_WATCHER = 'indexer.watcher';

    private const SERVICE_INDEXER_EXCLUDE_PATTERNS = 'indexer.exclude_patterns';
    private const SERVICE_INDEXER_INCLUDE_PATTERNS = 'indexer.include_patterns';
    private const INDEXER_TOLERANT = 'tolerant';
    private const INDEXER_WORSE = 'worse';
    private const SERVICE_FILESYSTEM = 'indexer.filesystem';
    private const PARAM_PROJECT_ROOT = 'indexer.project_root';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_ENABLED_WATCHERS => ['inotify', 'find', 'php'],
            self::PARAM_INDEX_PATH => '%cache%/index/%project_id%',
            self::PARAM_INCLUDE_PATTERNS => [
                '/**/*.php',
            ],
            self::PARAM_EXCLUDE_PATTERNS => [
                '/vendor/**/Tests/**/*',
                '/vendor/**/tests/**/*',
                '/vendor/composer/**/*',
            ],
            self::PARAM_INDEXER_POLL_TIME => 5000,
            self::PARAM_INDEXER_BUFFER_TIME => 500,
            self::PARAM_PROJECT_ROOT => '%project_root%',
        ]);
        $schema->setDescriptions([
            self::PARAM_ENABLED_WATCHERS => 'List of allowed watchers. The first watcher that supports the current system will be used',
            self::PARAM_INDEX_PATH => 'Path where the index should be saved',
            self::PARAM_INCLUDE_PATTERNS => 'Glob patterns to include while indexing',
            self::PARAM_EXCLUDE_PATTERNS => 'Glob patterns to exclude while indexing',
            self::PARAM_INDEXER_POLL_TIME => 'For polling indexers only: the time, in milliseconds, between polls (e.g. filesystem scans)',
            self::PARAM_INDEXER_BUFFER_TIME => 'For real-time indexers only: the time, in milliseconds, to buffer the results',
            self::PARAM_PROJECT_ROOT => 'The root path to use for scanning the index',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $this->registerCommands($container);
        $this->registerModel($container);
        $this->registerWorseAdapters($container);
        $this->registerRpc($container);
        $this->registerReferenceFinderAdapters($container);
        $this->registerWatcher($container);
    }

    private function createReflector(Container $container): Reflector
    {
        $builder = ReflectorBuilder::create();
        foreach (array_keys($container->getServiceIdsForTag(WorseReflectionExtension::TAG_SOURCE_LOCATOR)) as $serviceId) {
            $builder->addLocator($container->get($serviceId), 128);
        }
        $builder->enableCache();

        return $builder->build();
    }

    private function registerWorseAdapters(ContainerBuilder $container): void
    {
        $container->register(IndexBuilder::class, function (Container $container) {
            return TolerantIndexBuilder::create($container->get(IndexAccess::class));
        });
        
        $container->register(IndexerClassSourceLocator::class, function (Container $container) {
            return new IndexerClassSourceLocator($container->get(IndexAccess::class));
        }, [
            WorseReflectionExtension::TAG_SOURCE_LOCATOR => []
        ]);

        $container->register(IndexerFunctionSourceLocator::class, function (Container $container) {
            return new IndexerFunctionSourceLocator($container->get(IndexAccess::class));
        }, [
            WorseReflectionExtension::TAG_SOURCE_LOCATOR => []
        ]);
    }

    private function registerCommands(ContainerBuilder $container): void
    {
        $container->register(IndexBuildCommand::class, function (Container $container) {
            return new IndexBuildCommand(
                $container->get(Indexer::class),
                $container->get(Watcher::class)
            );
        }, [ ConsoleExtension::TAG_COMMAND => ['name' => 'index:build']]);
        
        $container->register(IndexQueryCommand::class, function (Container $container) {
            return new IndexQueryCommand($container->get(QueryClient::class));
        }, [ ConsoleExtension::TAG_COMMAND => ['name' => 'index:query']]);
    }

    private function registerModel(ContainerBuilder $container): void
    {
        $container->register(IndexAgent::class, function (Container $container) {
            return $container->get(IndexAgentBuilder::class)
                ->setReferenceEnhancer($container->get(WorseRecordReferenceEnhancer::class))
                ->buildAgent();
        });

        $container->register(IndexAccess::class, function (Container $container) {
            return $container->get(IndexAgentBuilder::class)
                ->buildAgent()->access();
        });

        $container->register(QueryClient::class, function (Container $container) {
            return $container->get(IndexAgent::class)->query();
        });

        $container->register(IndexAgentBuilder::class, function (Container $container) {
            $indexPath = $container->get(
                FilePathResolverExtension::SERVICE_FILE_PATH_RESOLVER
            )->resolve(
                $container->getParameter(self::PARAM_INDEX_PATH)
            );
            return IndexAgentBuilder::create($indexPath, $this->projectRoot($container))
                ->setExcludePatterns($container->get(self::SERVICE_INDEXER_EXCLUDE_PATTERNS))
                ->setIncludePatterns($container->get(self::SERVICE_INDEXER_INCLUDE_PATTERNS));
        });

        $container->register(Indexer::class, function (Container $container) {
            return $container->get(IndexAgent::class)->indexer();
        });

        $container->register(self::SERVICE_INDEXER_EXCLUDE_PATTERNS, function (Container $container) {
            $projectRoot = $this->projectRoot($container);
            return array_map(function (string $pattern) use ($projectRoot) {
                return Path::join([$projectRoot, $pattern]);
            }, $container->getParameter(self::PARAM_EXCLUDE_PATTERNS));
        });

        $container->register(self::SERVICE_INDEXER_INCLUDE_PATTERNS, function (Container $container) {
            $projectRoot = $container->getParameter(FilePathResolverExtension::PARAM_PROJECT_ROOT);
            return array_map(function (string $pattern) use ($projectRoot) {
                return Path::join([$projectRoot, $pattern]);
            }, $container->getParameter(self::PARAM_INCLUDE_PATTERNS));
        });
        
        $container->register(WorseRecordReferenceEnhancer::class, function (Container $container) {
            return new WorseRecordReferenceEnhancer(
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR),
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );
        });
    }

    private function registerReferenceFinderAdapters(ContainerBuilder $container): void
    {
        $container->register(IndexedImplementationFinder::class, function (Container $container) {
            return new IndexedImplementationFinder(
                $container->get(QueryClient::class),
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR)
            );
        }, [ ReferenceFinderExtension::TAG_IMPLEMENTATION_FINDER => []]);

        $container->register(IndexedReferenceFinder::class, function (Container $container) {
            return new IndexedReferenceFinder(
                $container->get(QueryClient::class),
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR),
            );
        }, [ ReferenceFinderExtension::TAG_REFERENCE_FINDER => []]);

        $container->register(IndexedNameSearcher::class, function (Container $container) {
            return new IndexedNameSearcher(
                $container->get(QueryClient::class)
            );
        }, [ ReferenceFinderExtension::TAG_NAME_SEARCHER => []]);
    }

    private function registerRpc(ContainerBuilder $container): void
    {
        $container->register(IndexHandler::class, function (Container $container) {
            return new IndexHandler(
                $container->get(Indexer::class),
                $container->get(Watcher::class)
            );
        }, [
            RpcExtension::TAG_RPC_HANDLER => [
                'name' => IndexHandler::NAME,
            ],
        ]);
    }

    private function registerWatcher(ContainerBuilder $container): void
    {
        $container->register(Watcher::class, function (Container $container) {
            $watchers = [];

            $enabledWatchers = $container->getParameter(self::PARAM_ENABLED_WATCHERS);

            foreach ($container->getServiceIdsForTag(self::TAG_WATCHER) as $serviceId => $attrs) {
                if (!isset($attrs['name'])) {
                    throw new RuntimeException(sprintf(
                        'Watcher "%s" must provide the `name` attribute',
                        $serviceId
                    ));
                }

                $watchers[$attrs['name']] = $serviceId;
            }

            if ($diff = array_diff($enabledWatchers, array_keys($watchers))) {
                throw new RuntimeException(sprintf(
                    'Unknown watchers "%s" specified, available watchers: "%s"',
                    implode('", "', $diff),
                    implode('", "', array_keys($watchers))
                ));
            }

            $watchers = array_filter(array_map(
                function (string $name, string $serviceId) use ($container, $enabledWatchers) {
                    if (!in_array($name, $enabledWatchers)) {
                        return null;
                    }

                    return $container->get($serviceId);
                },
                array_keys($watchers),
                array_values($watchers)
            ));

            if ($watchers === []) {
                return new NullWatcher();
            }

            return new PatternMatchingWatcher(
                new FallbackWatcher($watchers, $container->get(LoggingExtension::SERVICE_LOGGER)),
                $container->get(self::SERVICE_INDEXER_INCLUDE_PATTERNS),
                $container->get(self::SERVICE_INDEXER_EXCLUDE_PATTERNS)
            );
        });
        $container->register(WatcherConfig::class, function (Container $container) {
            $resolver = $container->get(FilePathResolverExtension::SERVICE_FILE_PATH_RESOLVER);
            assert($resolver instanceof PathResolver);

            // NOTE: the project root should NOT have a scheme in it (file://), but there is no validation
            // about this, so we parse it using the text document URI
            $path = TextDocumentUri::fromString($resolver->resolve('%project_root%'));

            return new WatcherConfig([
                $path->path()
            ], $container->getParameter(self::PARAM_INDEXER_POLL_TIME));
        });

        // register watchers - order of registration currently determines
        // priority

        $container->register(InotifyWatcher::class, function (Container $container) {
            return new BufferedWatcher(new InotifyWatcher(
                $container->get(WatcherConfig::class),
                $container->get(LoggingExtension::SERVICE_LOGGER)
            ), $container->getParameter(self::PARAM_INDEXER_BUFFER_TIME));
        }, [
            self::TAG_WATCHER => [
                'name' => 'inotify',
            ]
        ]);

        $container->register(FsWatchWatcher::class, function (Container $container) {
            return new FsWatchWatcher(
                $container->get(WatcherConfig::class),
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );
        }, [
            self::TAG_WATCHER => [
                'name' => 'fswatch',
            ]
        ]);

        $container->register(FindWatcher::class, function (Container $container) {
            return new FindWatcher(
                $container->get(WatcherConfig::class),
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );
        }, [
            self::TAG_WATCHER => [
                'name' => 'find',
            ]
        ]);

        $container->register(PhpPollWatcher::class, function (Container $container) {
            return new PhpPollWatcher(
                $container->get(WatcherConfig::class),
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );
        }, [
            self::TAG_WATCHER => [
                'name' => 'php',
            ]
        ]);
    }

    private function projectRoot(Container $container): string
    {
        return $container->get(
            FilePathResolverExtension::SERVICE_FILE_PATH_RESOLVER
        )->resolve($container->getParameter(self::PARAM_PROJECT_ROOT));
    }
}
