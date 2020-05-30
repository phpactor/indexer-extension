<?php

namespace Phpactor\Indexer\Adapter\ReferenceFinder;

use Generator;
use Phpactor\Indexer\Model\IndexQueryAgent;
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

    public function search(string $search): Generator
    {
        foreach ($this->client->search($search) as $result) {
            yield NameSearchResult::create($result->recordType(), FullyQualifiedName::fromString($result->identifier()));
        }
    }
}
