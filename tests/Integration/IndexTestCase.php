<?php

namespace Phpactor\Indexer\Tests\Integration;

use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Tests\IntegrationTestCase;
use function Safe\file_get_contents;

abstract class IndexTestCase extends IntegrationTestCase
{
    public function testBuild(): void
    {
        $index = $this->createIndex();
        $builder = $this->createTestBuilder($index);
        $indexer = new Indexer($builder, $index, $this->fileListProvider());
        $indexer->getJob()->run();
        $references = $foo = $this->indexQuery($index)->class()->implementing(
            'Index'
        );

        self::assertCount(2, $references);
    }

    protected function setUp(): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest(file_get_contents(__DIR__ . '/Manifest/buildIndex.php.test'));
    }
}
