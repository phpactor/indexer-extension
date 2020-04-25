<?php

namespace Phpactor\Indexer\Tests\Integration\Tolerant;

use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexBuilder;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Tests\Integration\IndexBuilderIndexTestCase;

class TolerantIndexBuilderTest extends IndexBuilderIndexTestCase
{
    protected function createBuilder(Index $index): IndexBuilder
    {
        return TolerantIndexBuilder::create($index);
    }
}
