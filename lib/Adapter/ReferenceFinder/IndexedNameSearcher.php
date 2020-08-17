<?php

namespace Phpactor\Indexer\Adapter\ReferenceFinder;

use Generator;
use Phpactor\Indexer\Model\Query\Criteria;
use Phpactor\Indexer\Model\SearchClient;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\ReferenceFinder\Search\NameSearchResult;

class IndexedNameSearcher implements NameSearcher
{
    /**
     * @var SearchClient
     */
    private $client;

    public function __construct(SearchClient $client)
    {
        $this->client = $client;
    }

    public function search(Criteria $criteria): Generator
    {
        foreach ($this->client->search($criteria) as $result) {
            yield NameSearchResult::create($result->recordType(), FullyQualifiedName::fromString($result->identifier()));
        }
    }
}
