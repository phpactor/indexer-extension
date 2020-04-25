<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\Record;

interface IndexQuery
{
    /**
     * @return array<FullyQualifiedName>
     */
    public function implementing(FullyQualifiedName $name): array;

    public function class(FullyQualifiedName $name): ?Record;

    public function function(FullyQualifiedName $fullyQualifiedName): ?FunctionRecord;
}
