<?php

namespace Phpactor\ProjectQuery\Tests\Integration;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\ProjectQuery\Adapter\Php\InMemory\InMemoryIndex;
use Phpactor\ProjectQuery\Adapter\Php\InMemory\InMemoryRepository;
use Phpactor\ProjectQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\ProjectQuery\Tests\IntegrationTestCase;
use function Safe\file_get_contents;

abstract class IndexBuilderIndexTestCase extends IntegrationTestCase
{
    public function testInterfaceImplementations(): void
    {
        $index = $this->buildIndex();

        $references = $foo = $index->query()->implementing(
            FullyQualifiedName::fromString('Index')
        );

        self::assertCount(2, $references);
    }

    public function testChildClassImplementations(): void
    {
        $index = $this->buildIndex();

        $references = $foo = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );

        self::assertCount(2, $references);
    }

    protected function setUp(): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest(file_get_contents(__DIR__ . '/Manifest/buildIndex.php.test'));
    }

    abstract protected function createBuilder(InMemoryIndex $index): WorseIndexBuilder;

    private function buildIndex(): InMemoryIndex
    {
        $repository = new InMemoryRepository();
        $index = new InMemoryIndex($repository);
        $indexBuilder = $this->createBuilder($index);
        iterator_to_array($indexBuilder->build());
        return $index;
    }
}
