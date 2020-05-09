<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\Record;
use Phpactor\Name\FullyQualifiedName;

final class FunctionRecord extends Record
{
    use FullyQualifiedReferenceTrait;
    use HasFileReferencesTrait;

    public const RECORD_TYPE = 'function';

    public static function fromName(string $name): self
    {
        return new self(FullyQualifiedName::fromString($name));
    }

    /**
     * {@inheritDoc}
     */
    public function recordType(): string
    {
        return self::RECORD_TYPE;
    }
}
