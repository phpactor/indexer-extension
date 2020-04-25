<?php

namespace Phpactor\Indexer\Adapter\Php\InMemory;

use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\IndexWriter;
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

    public function __construct(?InMemoryRepository $repository = null)
    {
        $this->repository = $repository ?: new InMemoryRepository();
        $this->lastUpdate = 0;
    }

    public function lastUpdate(): int
    {
        return $this->lastUpdate;
    }

    public function query(): IndexQuery
    {
        return new InMemoryQuery($this->repository);
    }

    public function write(Record $record): void
    {
        if ($record instanceof ClassRecord) {
            $this->repository->putClass($record);
            return;
        }

        if ($record instanceof FunctionRecord) {
            $this->repository->putFunction($record);
            return;
        }

        throw new RuntimeException(sprintf(
            'Do not know how to index "%s"',
            get_class($record)
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function get(Record $record): Record
    {
        if ($record instanceof ClassRecord) {
            return $this->repository->getClass($record->fqn()) ?? $record;
        }

        if ($record instanceof FunctionRecord) {
            return $this->repository->getFunction($record->fqn()) ?? $record;
        }

        throw new RuntimeException(sprintf(
            'Do not know how to index "%s"',
            get_class($record)
        ));
    }

    public function isFresh(SplFileInfo $fileInfo): bool
    {
        return false;
    }

    public function reset(): void
    {
        $this->repository->reset();
    }

    public function exists(): bool
    {
        return $this->repository->lastUpdate !== 0;
    }

    public function updateTimestamp(): void
    {
        $this->repository->lastUpdate = time();
    }
}
