<?php

namespace Phpactor\WorkspaceQuery\Extension;

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
use Phpactor\MapResolver\Resolver;
use Phpactor\WorkspaceQuery\Adapter\Filesystem\FilesystemFileListProvider;
use Phpactor\WorkspaceQuery\Adapter\Php\Serialized\FileRepository;
use Phpactor\WorkspaceQuery\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\WorkspaceQuery\Adapter\ReferenceFinder\IndexedImplementationFinder;
use Phpactor\WorkspaceQuery\Adapter\Worse\WorkspaceQuerySourceLocator;
use Phpactor\WorkspaceQuery\Extension\Command\IndexQueryClassCommand;
use Phpactor\WorkspaceQuery\Extension\Command\IndexBuildCommand;
use Phpactor\WorkspaceQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\WorkspaceQuery\Extension\Rpc\IndexHandler;
use Phpactor\WorkspaceQuery\Model\FileListProvider;
use Phpactor\WorkspaceQuery\Model\Index;
use Phpactor\WorkspaceQuery\Model\IndexBuilder;
use Phpactor\WorkspaceQuery\Model\IndexQuery;
use Phpactor\WorkspaceQuery\Model\Indexer;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\ReflectorBuilder;

class WorkspaceQueryExtension implements Extension
{
    const PARAM_INDEX_PATH = 'workspace_query.index_path';
    const PARAM_DEFAULT_FILESYSTEM = 'workspace_query.default_filesystem';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_INDEX_PATH => '%cache%/index/%project_id%',
            self::PARAM_DEFAULT_FILESYSTEM => SourceCodeFilesystemExtension::FILESYSTEM_COMPOSER,
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
        
        $container->register(WorkspaceQuerySourceLocator::class, function (Container $container) {
            return new WorkspaceQuerySourceLocator($container->get(Index::class));
        }, [
            WorseReflectionExtension::TAG_SOURCE_LOCATOR => []
        ]);
    }

    private function registerCommands(ContainerBuilder $container): void
    {
        $container->register(IndexBuildCommand::class, function (Container $container) {
            return new IndexBuildCommand(
                $container->get(Indexer::class)
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
            return new IndexHandler($container->get(Indexer::class));
        }, [
            RpcExtension::TAG_RPC_HANDLER => [
                'name' => IndexHandler::NAME,
            ],
        ]);
    }
}
