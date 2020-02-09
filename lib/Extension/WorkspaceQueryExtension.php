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
use Phpactor\WorkspaceQuery\Adapter\Php\Serialized\FileRepository;
use Phpactor\WorkspaceQuery\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\WorkspaceQuery\Adapter\ReferenceFinder\IndexedImplementationFinder;
use Phpactor\WorkspaceQuery\Adapter\Symfony\Console\IndexQueryClassCommand;
use Phpactor\WorkspaceQuery\Adapter\Symfony\Console\IndexRefreshCommand;
use Phpactor\WorkspaceQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\WorkspaceQuery\Model\Index;
use Phpactor\WorkspaceQuery\Model\IndexBuilder;
use Phpactor\WorkspaceQuery\Model\IndexQuery;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\ReflectorBuilder;

class WorkspaceQueryExtension implements Extension
{
    const PARAM_INDEX_PATH = 'project_query.index_path';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_INDEX_PATH => '%cache%/index/%project_id%',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $container->register(IndexRefreshCommand::class, function (Container $container) {
            return new IndexRefreshCommand(
                $container->get(IndexBuilder::class),
                $container->get(Index::class)
            );
        }, [ ConsoleExtension::TAG_COMMAND => ['name' => 'index:refresh']]);

        $container->register(IndexQueryClassCommand::class, function (Container $container) {
            return new IndexQueryClassCommand($container->get(IndexQuery::class));
        }, [ ConsoleExtension::TAG_COMMAND => ['name' => 'index:query:implementations']]);

        $container->register(IndexBuilder::class, function (Container $container) {
            return new WorseIndexBuilder(
                $container->get(Index::class),
                $container->get(SourceCodeFilesystemExtension::SERVICE_FILESYSTEM_COMPOSER),
                $this->createReflector($container),
                $container->get(LoggingExtension::SERVICE_LOGGER)
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

        $container->register(IndexedImplementationFinder::class, function (Container $container) {
            return new IndexedImplementationFinder(
                $container->get(Index::class),
                $container->get(IndexBuilder::class),
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

        return $builder->build();
    }
}
