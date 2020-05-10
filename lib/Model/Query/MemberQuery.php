<?php

namespace Phpactor\Indexer\Model\Query;

use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\Record\MemberRecord;

class MemberQuery implements IndexQuery
{
    /**
     * @var Index
     */
    private $index;

    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    public function get(string $identifier): ?MemberRecord
    {
        if (!MemberRecord::isIdentifier($identifier)) {
            return null;
        }

        return $this->index->get(MemberRecord::fromIdentifier($identifier));
    }
}
