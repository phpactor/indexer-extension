<?php

namespace Phpactor\Indexer\Extension;

use Phpactor\AmpFsWatch\Watcher;
use Phpactor\AmpFsWatch\WatcherConfig;
use Phpactor\AmpFsWatch\Watcher\Fallback\FallbackWatcher;
use Phpactor\AmpFsWatch\Watcher\Find\FindWatcher;
use Phpactor\AmpFsWatch\Watcher\FsWatch\FsWatchWatcher;
use Phpactor\AmpFsWatch\Watcher\Inotify\InotifyWatcher;
use Phpactor\AmpFsWatch\Watcher\PatternMatching\PatternMatchingWatcher;
use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\ReferenceFinder\ReferenceFinderExtension;
use Phpactor\Extension\Rpc\RpcExtension;
use Phpactor\Extension\SourceCodeFilesystem\SourceCodeFilesystemExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\FilePathResolver\PathResolver;
use Phpactor\Indexer\Adapter\Worse\IndexerSourceLocator;
use Phpactor\MapResolver\Resolver;
use Phpactor\Indexer\Adapter\Filesystem\FilesystemFileListProvider;
use Phpactor\Indexer\Adapter\Php\Serialized\FileRepository;
use Phpactor\Indexer\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\Indexer\Adapter\ReferenceFinder\IndexedImplementationFinder;
use Phpactor\Indexer\Extension\Command\IndexQueryClassCommand;
use Phpactor\Indexer\Extension\Command\IndexBuildCommand;
use Phpactor\Indexer\Adapter\Worse\WorseIndexBuilder;
use Phpactor\Indexer\Extension\Rpc\IndexHandler;
use Phpactor\Indexer\Extension\LanguageServer\IndexerHandler as LsIndexerHandler;
use Phpactor\Indexer\Model\FileListProvider;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\TextDocument\TextDocumentUri;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\ReflectorBuilder;

class IndexerExtension implements Extension
{
    const PARAM_INDEX_PATH = 'indexer.index_path';
    const PARAM_DEFAULT_FILESYSTEM = 'indexer.default_filesystem';
    const PARAM_INDEX_PATTERNS = 'indexer.index_patterns';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_INDEX_PATH => '%cache%/index/%project_id%',
            self::PARAM_DEFAULT_FILESYSTEM => SourceCodeFilesystemExtension::FILESYSTEM_COMPOSER,
            self::PARAM_INDEX_PATTERNS => [ '*.php' ],
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
        $this->registerLanguageServer($container);
    }

    private function createReflector(Container $container): Reflector
    {
        $builder = ReflectorBuilder::create();
        foreach (array_keys($container->getServiceIdsForTag(WorseReflectionExtension::TAG_SOURCE_LOCATOR)) as $serviceId) {
            $builder->addLocator($container->get($serviceId));
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

    private function registerLanguageServer(ContainerBuilder $container): void
    {
        $container->register(LsIndexerHandler::class, function (Container $container) {
            return new LsIndexerHandler(
                $container->get(Indexer::class),
                $container->get(Watcher::class)
            );
        }, [
            LanguageServerExtension::TAG_SESSION_HANDLER => []
        ]);

        $container->register(Watcher::class, function (Container $container) {
            $resolver = $container->get(FilePathResolverExtension::SERVICE_FILE_PATH_RESOLVER);
            assert($resolver instanceof PathResolver);

            // NOTE: the project root should NOT have a scheme in it (file://), but there is no validation
            // about this, so we parse it using the text document URI
            $path = TextDocumentUri::fromString($resolver->resolve('%project_root%'));

            $config = new WatcherConfig([$path->path()], 5000);

            return new PatternMatchingWatcher(new FallbackWatcher([
                new InotifyWatcher($config, $container->get(LoggingExtension::SERVICE_LOGGER)),
                new FsWatchWatcher($config, $container->get(LoggingExtension::SERVICE_LOGGER)),
                new FindWatcher($config, $container->get(LoggingExtension::SERVICE_LOGGER)),
            ], $container->get(LoggingExtension::SERVICE_LOGGER)), $container->getParameter(self::PARAM_INDEX_PATTERNS));
        });
    }
}
