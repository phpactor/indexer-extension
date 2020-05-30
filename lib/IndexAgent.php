<?php

namespace Phpactor\Indexer;

use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\QueryClient;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Model\SearchClient;
use Phpactor\Indexer\Model\SearchIndex;

interface IndexAgent
{
    public function search(): SearchClient;

    public function query(): QueryClient;

    public function indexer(): Indexer;
}
