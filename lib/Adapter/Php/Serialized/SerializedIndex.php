<?php

namespace Phpactor\Indexer\Adapter\Php\Serialized;

use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\IndexWriter;
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

    public function write(): IndexWriter
    {
        return new SerializedWriter($this->repository);
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
}
