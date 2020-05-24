<?php

namespace Phpactor\Indexer\Adapter\Php\Serialized;

use Generator;
use Phpactor\Indexer\Adapter\Php\InMemory\InMemorySearchIndex;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\SearchIndex;
use RuntimeException;
use SplFileInfo;

class SerializedIndex implements Index
{
    /**
     * @var FileRepository
     */
    private $repository;

    /**
     * @var SearchIndex
     */
    private $search;

    public function __construct(FileRepository $repository, ?SearchIndex $search = null)
    {
        $this->repository = $repository;
        $this->search = $search ?: new InMemorySearchIndex();
    }

    public function lastUpdate(): int
    {
        return $this->repository->lastUpdate();
    }

    public function get(Record $record): Record
    {
        return $this->repository->get($record) ?? $record;
    }

    public function write(Record $record): void
    {
        $this->repository->put($record);
        $this->search->write($record);
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

    public function done(): void
    {
        $this->repository->flush();
        $this->repository->putTimestamp();
    }

    public function has(Record $record): bool
    {
        return $this->repository->get($record) ? true : false;
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $query): Generator
    {
        yield from $this->search->search($query);
    }
}
