<?php

namespace Phpactor\ProjectQuery\Tests\Integration;

use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\ProjectQuery\Adapter\Php\InMemoryIndex;
use Phpactor\ProjectQuery\Adapter\Php\InMemoryRepository;
use Phpactor\ProjectQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\ProjectQuery\Model\Index;
use Phpactor\ProjectQuery\Tests\IntegrationTestCase;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\ReflectorBuilder;
use Symfony\Component\Filesystem\Filesystem;
use function Safe\file_get_contents;

abstract class AbstractIndexBuilderIndexTestCase extends IntegrationTestCase
{
    public function testBuildIndex(): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest(file_get_contents(__DIR__ . '/Manifest/buildIndex.php.test'));

        $repository = new InMemoryRepository();
        $index = new InMemoryIndex($repository);
        $indexBuilder = $this->createBuilder($index);
        $indexBuilder->refresh();

        $references = $foo = $index->query()->implementing(
            FullyQualifiedName::fromString('Index')
        );

        self::assertCount(1, $references);
    }

    protected function setUp(): void
    {
    }

    abstract function createBuilder(InMemoryIndex $index): WorseIndexBuilder;
}
