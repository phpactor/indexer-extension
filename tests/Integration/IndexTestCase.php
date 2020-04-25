<?php

namespace Phpactor\Indexer\Tests\Integration;

use Phpactor\Indexer\Adapter\Worse\WorseIndexBuilder;
use Phpactor\Indexer\Model\IndexBuilder;
use Psr\Log\NullLogger;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Name\FullyQualifiedName;
use function Safe\file_get_contents;

abstract class IndexTestCase extends InMemoryTestCase
{
    public function testBuild(): void
    {
        $index = $this->createIndex();
        $builder = $this->createTestBuilder($index);
        $indexer = new Indexer($builder, $index, $this->fileListProvider($index));
        $indexer->getJob()->run();
        $references = $foo = $index->query()->implementing(
            FullyQualifiedName::fromString('Index')
        );

        self::assertCount(2, $references);
    }

    protected function setUp(): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest(file_get_contents(__DIR__ . '/Manifest/buildIndex.php.test'));
    }
}
