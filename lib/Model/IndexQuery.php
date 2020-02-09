<?php

namespace Phpactor\WorkspaceQuery\Model;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\WorkspaceQuery\Model\Record\ClassRecord;

interface IndexQuery
{
    /**
     * @return array<FullyQualifiedName>
     */
    public function implementing(FullyQualifiedName $name): array;

    public function class(FullyQualifiedName $name): ?ClassRecord;
}
