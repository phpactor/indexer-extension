<?php

namespace Phpactor\Indexer\Tests\Adapter\Tolerant;

use Phpactor\Filesystem\Adapter\Simple\SimpleFileListProvider;
use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\Indexer\Adapter\Filesystem\FilesystemFileListProvider;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexBuilder;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Indexer\Model\TestIndexAgent;
use Phpactor\Indexer\Tests\IntegrationTestCase;

class TolerantIndexerTestCase extends IntegrationTestCase
{
    protected function runIndexer(TolerantIndexer $indexer, string $path): TestIndexAgent
    {
        // run the indexer twice - the results should not be affected
        $this->doRunIndexer($indexer, $path);
        return $this->doRunIndexer($indexer, $path);
    }

    private function doRunIndexer(TolerantIndexer $indexer, string $path): TestIndexAgent
    {
        $agent = $this->indexAgentBuilder('src')
            ->setIndexers([
                $indexer
            ])->buildTestAgent();

        $agent->indexer()->getJob()->run();

        return $agent;
    }
}
