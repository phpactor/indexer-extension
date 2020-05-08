<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\RecordReference;
use SplFileInfo;

class FileRecord extends Record
{
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
            $reference->offset()
        ];

        return $this;
    }

    /**
     * @return array<RecordReference>
     */
    public function references(): array
    {
        return array_map(function (array $reference) {
            return new RecordReference(...$reference);
        }, $this->references);
    }

    /**
     * @return array<RecordReference>
     */
    public function referencesTo(Record $record): array
    {
        return array_filter($this->references(), function (RecordReference $reference) use ($record) {
            return $reference->type() === $record->recordType() && $reference->identifier() === $record->identifier();
        });
    }
}
