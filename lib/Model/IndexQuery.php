<?php

namespace Phpactor\ProjectQuery\Model;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\ProjectQuery\Model\Record\ClassRecord;

interface IndexQuery
{
    /**
     * @return array<FullyQualifiedName>
     */
    public function implementing(FullyQualifiedName $name): array;

    public function class(FullyQualifiedName $name): ?ClassRecord;
}