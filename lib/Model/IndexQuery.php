<?php

namespace Phpactor\ProjectQuery\Model;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\ProjectQuery\Model\Record\ClassRecord;

interface IndexQuery
{
    public function implementing(FullyQualifiedName $name): References;

    public function class(FullyQualifiedName $name): ?ClassRecord;
}
