<?php

namespace Phpactor\Indexer;

use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\IndexQueryAgent;
use Phpactor\Indexer\Model\SearchClient;
use Phpactor\Indexer\Model\SearchIndex;

class IndexAgent
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
     * @var IndexBuilder
     */
    private $builder;

    public function __construct(IndexQueryAgent $query, SearchClient $search, IndexBuilder $builder)
    {
        $this->query = $query;
        $this->search = $search;
        $this->builder = $builder;
    }

    public function search(): SearchClient
    {
        return $this->search;
    }

    public function builder(): IndexBuilder
    {
        return $this->builder;
    }

    public function query(): IndexQueryAgent
    {
        return $this->query;
    }
}
