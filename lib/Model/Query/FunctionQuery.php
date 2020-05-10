<?php

namespace Phpactor\Indexer\Model\Query;

use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\Record\FunctionRecord;

class FunctionQuery implements IndexQuery
{
    /**
     * @var Index
     */
    private $index;

    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    public function get(string $identifier): ?FunctionRecord
    {
        return $this->index->get(FunctionRecord::fromName($identifier));
    }
}
