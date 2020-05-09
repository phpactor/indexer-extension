<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Indexer\Model\Record\MemberRecord;
use Phpactor\Name\FullyQualifiedName;

interface IndexQuery
{
    /**
     * @return array<FullyQualifiedName>
     */
    public function implementing(FullyQualifiedName $name): array;

    public function class(FullyQualifiedName $name): ?ClassRecord;

    public function function(FullyQualifiedName $fullyQualifiedName): ?FunctionRecord;

    public function file(string $path): ?FileRecord;

    public function member(string $name): ?MemberRecord;
}
