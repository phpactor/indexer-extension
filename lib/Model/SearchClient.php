<?php

namespace Phpactor\Indexer\Model;

use Generator;

interface SearchClient
{
    /**
     * @return Generator<Record>
     */
    public function search(string $query): Generator;
}
