<?php

namespace Phpactor\ProjectQuery\Tests\Integration;

use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\ProjectQuery\Adapter\Php\InMemory\InMemoryIndex;
use Phpactor\ProjectQuery\Adapter\Php\InMemory\InMemoryRepository;
use Phpactor\ProjectQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\ProjectQuery\Model\Index;
use Phpactor\ProjectQuery\Tests\IntegrationTestCase;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\ReflectorBuilder;
use Symfony\Component\Filesystem\Filesystem;
use function Safe\file_get_contents;

abstract class IndexTestCase extends IntegrationTestCase
{
    public function testBuild(): void
    {
        $index = $this->createIndex();
        $builder = $this->createBuilder($index);
        $builder->refresh();
        $references = $foo = $index->query()->implementing(
            FullyQualifiedName::fromString('Index')
        );

        self::assertCount(2, $references);
    }

    protected function createBuilder(Index $index): WorseIndexBuilder
    {
        $indexBuilder = new WorseIndexBuilder(
            $this->createIndex(),
            new SimpleFilesystem($this->workspace()->path('/project')),
            ReflectorBuilder::create()->addLocator(
                new StubSourceLocator(
                    ReflectorBuilder::create()->build(),
                    $this->workspace()->path('/'),
                    $this->workspace()->path('/')
                )
            )->build()
        );
        return $indexBuilder;
    }

    protected function setUp(): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest(file_get_contents(__DIR__ . '/Manifest/buildIndex.php.test'));

    }

    abstract protected function createIndex(): Index;
}
