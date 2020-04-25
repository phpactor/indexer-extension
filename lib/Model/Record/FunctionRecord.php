<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\Record;
use Phpactor\Name\FullyQualifiedName;

final class FunctionRecord extends Record
{
    public static function fromName(string $name): self
    {
        return new self(FullyQualifiedName::fromString($name));
    }
}
