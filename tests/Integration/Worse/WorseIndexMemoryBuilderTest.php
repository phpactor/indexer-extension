<?php

namespace Phpactor\Indexer\Tests\Integration\Worse;

use Phpactor\Indexer\Adapter\Worse\WorseIndexBuilder;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Tests\Integration\IndexBuilderIndexTestCase;
use Psr\Log\NullLogger;

class WorseIndexMemoryBuilderTest extends IndexBuilderIndexTestCase
{
    protected function createBuilder(Index $index): IndexBuilder
    {
        $indexBuilder = new WorseIndexBuilder(
            $index,
            $this->createReflector(),
            new NullLogger()
        );
        return $indexBuilder;
    }

    public function testIndexesClassLike(): void
    {
        $this->markTestSkipped();
    }
}
