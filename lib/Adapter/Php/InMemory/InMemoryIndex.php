<?php

namespace Phpactor\Indexer\Adapter\Php\InMemory;

use Generator;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexQueryAgent;
use RuntimeException;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record;
use SplFileInfo;

class InMemoryIndex implements Index
{
    /**
     * @var InMemoryRepository
     */
    private $repository;

    /**
     * @var int
     */
    private $lastUpdate;

    /**
     * @var InMemorySearchIndex
     */
    private $searchIndex;

    /**
     * @var array<Record>
     */
    private $index;

    /**
     * @param array<Record> $index
     */
    public function __construct(array $index = [])
    {
        $this->searchIndex = new InMemorySearchIndex();
        $this->lastUpdate = 0;
        $this->index = $index;
    }

    public function lastUpdate(): int
    {
        return $this->lastUpdate;
    }

    public function query(): IndexQueryAgent
    {
        return new IndexQueryAgent($this);
    }

    public function write(Record $record): void
    {
        $this->index[$this->recordKey($record)] = $record;
        $this->searchIndex->write($record);
    }

    public function get(Record $record): Record
    {
        $key = $this->recordKey($record);

        if (isset($this->index[$key])) {
            /** @phpstan-ignore-next-line */
            return $this->index[$key];
        }

        return $record;
    }

    public function isFresh(SplFileInfo $fileInfo): bool
    {
        return false;
    }

    public function reset(): void
    {
        $this->index = [];
    }

    public function exists(): bool
    {
        return $this->repository->lastUpdate !== 0;
    }

    public function done(): void
    {
        $this->repository->lastUpdate = time();
    }

    public function has(Record $record): bool
    {
        return isset($this->index[$this->recordKey($record)]);
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $search): Generator
    {
        yield from $this->searchIndex->search($search);
    }

    private function recordKey(Record $record): string
    {
        return $record->recordType().$record->identifier();
    }
}
