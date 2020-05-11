<?php

namespace Phpactor\Indexer\Tests\Integration\Tolerant;

use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexBuilder;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Indexer\Tests\IntegrationTestCase;

class TolerantIndexerTestCase extends IntegrationTestCase
{
    protected function runIndexer(TolerantIndexer $indexer, Index $index, string $path): Index
    {
        // run the indexer twice - the results should not be affected
        $this->doRunIndexer($index, $indexer, $path);
        $index = $this->doRunIndexer($index, $indexer, $path);

        return $index;
    }

    private function doRunIndexer(Index $index, TolerantIndexer $indexer, string $path): Index
    {
        $indexBuilder = new TolerantIndexBuilder($index, [ $indexer ]);
        $fileList = $this->fileListProvider($path);
        $indexer = new Indexer($indexBuilder, $index, $fileList);
        $indexer->getJob()->run();
        
        return $index;
    }
}
