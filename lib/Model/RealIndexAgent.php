<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Indexer\IndexAgent;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\QueryClient;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Model\SearchClient;
use Phpactor\Indexer\Model\SearchIndex;

class RealIndexAgent implements IndexAgent, TestIndexAgent
{
    /**
     * @var IndexQueryAgent
     */
    private $query;

    /**
     * @var SearchClient
     */
    private $search;

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var Index
     */
    private $index;

    public function __construct(Index $index, QueryClient $query, SearchClient $search, Indexer $indexer)
    {
        $this->query = $query;
        $this->search = $search;
        $this->indexer = $indexer;
        $this->index = $index;
    }

    public function search(): SearchClient
    {
        return $this->search;
    }

    public function query(): QueryClient
    {
        return $this->query;
    }

    public function indexer(): Indexer
    {
        return $this->indexer;
    }

    public function index(): Index
    {
        return $this->index;
    }

    public function access(): IndexAccess
    {
        return $this->index;
    }
}
