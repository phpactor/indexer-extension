<?php

namespace Phpactor\Indexer\Model;

use Generator;

interface SearchIndex
{
    /**
     * @return Generator<Record>
     */
    public function search(string $query): Generator;

    public function write(Record $record): void;

    public function flush(): void;
}
