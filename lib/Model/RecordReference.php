<?php

namespace Phpactor\Indexer\Model;

class RecordReference
{
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $identifier;
    /**
     * @var int
     */
    private $offset;

    public function __construct(string $type, string $identifier, int $offset)
    {
        $this->type = $type;
        $this->identifier = $identifier;
        $this->offset = $offset;
    }

    public function offset(): int
    {
        return $this->offset;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function type(): string
    {
        return $this->type;
    }

    public static function fromRecordAndOffset(Record $record, int $offset): self
    {
        return new self($record->recordType(), $record->identifier(), $offset);
    }
}
