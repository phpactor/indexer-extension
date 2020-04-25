<?php

namespace Phpactor\Indexer\Adapter\Php\Serialized;

use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\IndexWriter;
use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use RuntimeException;
use SplFileInfo;

class SerializedIndex implements Index
{
    /**
     * @var FileRepository
     */
    private $repository;

    public function __construct(FileRepository $repository)
    {
        $this->repository = $repository;
    }

    public function lastUpdate(): int
    {
        return $this->repository->lastUpdate();
    }

    public function query(): IndexQuery
    {
        return new SerializedQuery($this->repository);
    }

    public function get(Record $record): Record
    {
        if ($record instanceof ClassRecord) {
            return $this->repository->getClass($record->fqn()) ?? $record;
        }

        if ($record instanceof FunctionRecord) {
            return $this->repository->getFunction($record->fqn()) ?? $record;
        }

        throw new RuntimeException(sprintf(
            'Do not know how to get "%s"',
            get_class($record)
        ));
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

    public function isFresh(SplFileInfo $fileInfo): bool
    {
        try {
            $mtime = $fileInfo->getCTime();
        } catch (RuntimeException $statFailed) {
            // file likely doesn't exist
            return false;
        }

        return $mtime < $this->lastUpdate();
    }

    public function reset(): void
    {
        $this->repository->reset();
    }

    public function exists(): bool
    {
        return $this->repository->lastUpdate() > 0;
    }

    public function updateTimestamp(): void
    {
        $this->repository->putTimestamp();
    }
}
