<?php

namespace Phpactor\WorkspaceQuery\Extension;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\ReferenceFinder\ReferenceFinderExtension;
use Phpactor\Extension\SourceCodeFilesystem\SourceCodeFilesystemExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\MapResolver\Resolver;
use Phpactor\WorkspaceQuery\Adapter\Filesystem\FilesystemFileListProvider;
use Phpactor\WorkspaceQuery\Adapter\Php\Serialized\FileRepository;
use Phpactor\WorkspaceQuery\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\WorkspaceQuery\Adapter\ReferenceFinder\IndexedImplementationFinder;
use Phpactor\WorkspaceQuery\Extension\Command\IndexQueryClassCommand;
use Phpactor\WorkspaceQuery\Extension\Command\IndexBuildCommand;
use Phpactor\WorkspaceQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\WorkspaceQuery\Model\FileListProvider;
use Phpactor\WorkspaceQuery\Model\Index;
use Phpactor\WorkspaceQuery\Model\IndexBuilder;
use Phpactor\WorkspaceQuery\Model\IndexBuilder\NullIndexUpdater;
use Phpactor\WorkspaceQuery\Model\IndexQuery;
use Phpactor\WorkspaceQuery\Model\IndexUpdater;
use Phpactor\WorkspaceQuery\Model\Indexer;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\ReflectorBuilder;

class WorkspaceQueryExtension implements Extension
{
    const PARAM_INDEX_PATH = 'workspace_query.index_path';
    const PARAM_AUTO_REBUILD_INDEX = 'workspace_query.auto_rebuild_index';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_INDEX_PATH => '%cache%/index/%project_id%',
            self::PARAM_AUTO_REBUILD_INDEX => true,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $container->register(IndexBuildCommand::class, function (Container $container) {
            return new IndexBuildCommand(
                $container->get(Indexer::class)
            );
        }, [ ConsoleExtension::TAG_COMMAND => ['name' => 'index:build']]);

        $container->register(Indexer::class, function (Container $container) {
            return new Indexer(
                $container->get(IndexBuilder::class),
                $container->get(Index::class),
                $container->get(FileListProvider::class)
            );
        });
        $container->register(IndexQueryClassCommand::class, function (Container $container) {
            return new IndexQueryClassCommand($container->get(IndexQuery::class));
        }, [ ConsoleExtension::TAG_COMMAND => ['name' => 'index:query:class']]);

        $container->register(IndexBuilder::class, function (Container $container) {
            return new WorseIndexBuilder(
                $container->get(Index::class),
                $this->createReflector($container),
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );
        });

        $container->register(FileListProvider::class, function (Container $container) {
            return new FilesystemFileListProvider(
                $container->get(SourceCodeFilesystemExtension::SERVICE_FILESYSTEM_COMPOSER),
            );
        });

        $container->register(IndexUpdater::class, function (Container $container) {
            if ($container->getParameter(self::PARAM_AUTO_REBUILD_INDEX)) {
                return $container->get(IndexBuilder::class);
            }

            return new NullIndexUpdater();
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

        $container->register(IndexedImplementationFinder::class, function (Container $container) {
            return new IndexedImplementationFinder(
                $container->get(Index::class),
                $this->createReflector($container)
            );
        }, [ ReferenceFinderExtension::TAG_IMPLEMENTATION_FINDER => []]);
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
}
