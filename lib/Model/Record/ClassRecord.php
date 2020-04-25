<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\Record;
use Phpactor\Name\FullyQualifiedName;

class ClassRecord extends Record
{
    public static function fromName(string $name): self
    {
        return new self(FullyQualifiedName::fromString($name));
    }
}
