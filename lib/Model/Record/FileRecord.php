<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\Exception\CorruptedRecord;
use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\RecordReferences;
use SplFileInfo;

class FileRecord implements HasPath, Record
{
    use HasPathTrait;

    /**
     * @var array<array{string,string,int}>
     */
    private $references = [];

    public function __construct(string $filePath)
    {
        $this->setFilePath($filePath);
    }

    /**
     * {@inheritDoc}
     */
    public function recordType(): string
    {
        return 'file';
    }

    public static function fromFileInfo(SplFileInfo $info): self
    {
        return new self($info->getPathname());
    }

    public static function fromPath(string $path): self
    {
        return new self($path);
    }

    public function identifier(): string
    {
        return $this->filePath();
    }

    public function addReference(RecordReference $reference): self
    {
        $this->references[] = [
            $reference->type(),
            $reference->identifier(),
            $reference->offset(),
            $reference->contaninerType()
        ];

        return $this;
    }

    public function references(): RecordReferences
    {
        return new RecordReferences(array_map(function (array $reference) {
            return new RecordReference(...$reference);
        }, $this->references));
    }

    public function referencesTo(Record $record): RecordReferences
    {
        return new RecordReferences(array_filter($this->references()->toArray(), function (RecordReference $reference) use ($record) {
            return $reference->type() === $record->recordType() && $reference->identifier() === $record->identifier();
        }));
    }

    public function __wakeup(): void
    {
        if (null === $this->filePath) {
            throw new CorruptedRecord(sprintf(
                'Record was corrupted'
            ));
        }
    }
}
