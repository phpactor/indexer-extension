<?php

namespace Phpactor\Indexer\Model;

use Generator;

interface SearchIndex extends SearchClient
{
    public function write(Record $record): void;

    public function flush(): void;
}
