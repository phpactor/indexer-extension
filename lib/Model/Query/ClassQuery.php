<?php

namespace Phpactor\Indexer\Model\Query;

use Phpactor\Indexer\Model\Index;

class ClassQuery
{
    /**
     * @var Index
     */
    private $index;

    public function __construct(Index $index)
    {
        $this->index = $index;
    }
}
