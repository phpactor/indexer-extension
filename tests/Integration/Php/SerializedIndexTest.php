<?php

namespace Phpactor\Indexer\Tests\Integration\Php;

use Phpactor\Indexer\Adapter\Php\Serialized\FileRepository;
use Phpactor\Indexer\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Tests\Integration\IndexTestCase;

class SerializedIndexTest extends IndexTestCase
{
    protected function createIndex(): Index
    {
        return new SerializedIndex(new FileRepository(
            $this->workspace()->path('cache')
        ));
    }
}
