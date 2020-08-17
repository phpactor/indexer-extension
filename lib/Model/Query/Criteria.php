<?php

namespace Phpactor\Indexer\Model\Query;

use Phpactor\Indexer\Model\Record;

interface Criteria
{
    public function isSatisfiedBy(Record $record): bool;
}
