<?php

namespace Phpactor\Indexer\Extension;

use Phpactor\AmpFsWatch\Watcher;
use Phpactor\AmpFsWatch\WatcherConfig;
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
use Phpactor\Extension\SourceCodeFilesystem\SourceCodeFilesystemExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\FilePathResolver\PathResolver;
use Phpactor\Indexer\Adapter\Worse\IndexerSourceLocator;
use Phpactor\Indexer\Extension\Command\IndexQueryFunctionCommand;
use Phpactor\MapResolver\Resolver;
use Phpactor\Indexer\Adapter\Filesystem\FilesystemFileListProvider;
use Phpactor\Indexer\Adapter\Php\Serialized\FileRepository;
use Phpactor\Indexer\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\Indexer\Adapter\ReferenceFinder\IndexedImplementationFinder;
use Phpactor\Indexer\Extension\Command\IndexQueryClassCommand;
use Phpactor\Indexer\Extension\Command\IndexBuildCommand;
use Phpactor\Indexer\Adapter\Worse\WorseIndexBuilder;
use Phpactor\Indexer\Extension\Rpc\IndexHandler;
use Phpactor\Indexer\Model\FileListProvider;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\TextDocument\TextDocumentUri;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\ReflectorBuilder;
use RuntimeException;

class IndexerExtension implements Extension
{
    const PARAM_INDEX_PATH = 'indexer.index_path';
    const PARAM_DEFAULT_FILESYSTEM = 'indexer.default_filesystem';
    const PARAM_INDEX_PATTERNS = 'indexer.index_patterns';
    const PARAM_INDEXER_POLL_TIME = 'indexer.poll_time';
    const PARAM_ENABLED_WATCHERS = 'indexer.enabled_watchers';

    const TAG_WATCHER = 'indexer.watcher';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_ENABLED_WATCHERS => ['inotify', 'find', 'php'],
            self::PARAM_INDEX_PATH => '%cache%/index/%project_id%',
            self::PARAM_DEFAULT_FILESYSTEM => SourceCodeFilesystemExtension::FILESYSTEM_SIMPLE,
            self::PARAM_INDEX_PATTERNS => [ '*.php' ],
            self::PARAM_INDEXER_POLL_TIME => 5000,
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
            return new WorseIndexBuilder(
                $container->get(Index::class),
                $this->createReflector($container),
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );
        });
        
        $container->register(IndexerSourceLocator::class, function (Container $container) {
            return new IndexerSourceLocator($container->get(Index::class));
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
        
        $container->register(IndexQueryClassCommand::class, function (Container $container) {
            return new IndexQueryClassCommand($container->get(IndexQuery::class));
        }, [ ConsoleExtension::TAG_COMMAND => ['name' => 'index:query:class']]);

        $container->register(IndexQueryFunctionCommand::class, function (Container $container) {
            return new IndexQueryFunctionCommand($container->get(IndexQuery::class));
        }, [ ConsoleExtension::TAG_COMMAND => ['name' => 'index:query:function']]);
    }

    private function registerModel(ContainerBuilder $container): void
    {
        $container->register(Indexer::class, function (Container $container) {
            return new Indexer(
                $container->get(IndexBuilder::class),
                $container->get(Index::class),
                $container->get(FileListProvider::class)
            );
        });
        
        $container->register(FileListProvider::class, function (Container $container) {
            return new FilesystemFileListProvider(
                $container->get(SourceCodeFilesystemExtension::SERVICE_REGISTRY),
                $container->getParameter(self::PARAM_DEFAULT_FILESYSTEM)
            );
        });
        
        $container->register(Index::class, function (Container $container) {
            $repository = new FileRepository(
                $container->get(
                    FilePathResolverExtension::SERVICE_FILE_PATH_RESOLVER
                )->resolve(
                    $container->getParameter(self::PARAM_INDEX_PATH)
                )
            );
            return new SerializedIndex($repository);
        });
        
        $container->register(IndexQuery::class, function (Container $container) {
            $index = $container->get(Index::class);
            assert($index instanceof Index);
            return $index->query();
        });
    }

    private function registerReferenceFinderAdapters(ContainerBuilder $container): void
    {
        $container->register(IndexedImplementationFinder::class, function (Container $container) {
            return new IndexedImplementationFinder(
                $container->get(Index::class),
                $this->createReflector($container)
            );
        }, [ ReferenceFinderExtension::TAG_IMPLEMENTATION_FINDER => []]);
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
                $container->getParameter(self::PARAM_INDEX_PATTERNS)
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
            return new InotifyWatcher(
                $container->get(WatcherConfig::class),
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );
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
}
