<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\Record\ClassRecord;

interface IndexQuery
{
    /**
     * @return array<FullyQualifiedName>
     */
    public function implementing(FullyQualifiedName $name): array;

    public function class(FullyQualifiedName $name): ?ClassRecord;
}
