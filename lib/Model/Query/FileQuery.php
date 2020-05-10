<?php

namespace Phpactor\Indexer\Model\Query;

use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\Record\FileRecord;

class FileQuery implements IndexQuery
{
    /**
     * @var Index
     */
    private $index;

    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    public function get(string $identifier): ?FileRecord
    {
        return $this->index->get(FileRecord::fromPath($identifier));
    }
}
