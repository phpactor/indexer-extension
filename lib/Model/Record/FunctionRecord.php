<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\Record;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\TextDocument\ByteOffset;

class FunctionRecord extends Record
{
    public static function fromName(string $name): self
    {
        return new self(FullyQualifiedName::fromString($name));
    }
}
