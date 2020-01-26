<?php

namespace Phpactor\ProjectQuery\Extension;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\SourceCodeFilesystem\SourceCodeFilesystemExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\MapResolver\Resolver;
use Phpactor\ProjectQuery\Adapter\Php\Serialized\FileRepository;
use Phpactor\ProjectQuery\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\ProjectQuery\Adapter\Symfony\Console\IndexQueryClassCommand;
use Phpactor\ProjectQuery\Adapter\Symfony\Console\IndexRefreshCommand;
use Phpactor\ProjectQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\ProjectQuery\Model\Index;
use Phpactor\ProjectQuery\Model\IndexBuilder;
use Phpactor\ProjectQuery\Model\IndexQuery;
use Phpactor\ProjectQuery\Model\IndexWriter;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\ReflectorBuilder;

class ProjectQueryExtension implements Extension
{
    const PARAM_INDEX_PATH = 'project_query.index_path';

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $container->register(IndexRefreshCommand::class, function (Container $container) {
            return new IndexRefreshCommand($container->get(IndexBuilder::class));
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
    }

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_INDEX_PATH => '%cache%/index',
        ]);
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
