<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\Record;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\Record\HasFullyQualifiedName;

final class FunctionRecord implements HasFileReferences, HasPath, Record, HasFullyQualifiedName, HasShortName
{
    use FullyQualifiedReferenceTrait;
    use HasFileReferencesTrait;
    use HasPathTrait;

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

    public function shortName(): string
    {
        return $this->fqn()->head()->__toString();
    }
}
