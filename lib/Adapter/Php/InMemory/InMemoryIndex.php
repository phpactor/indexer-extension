<?php

namespace Phpactor\Indexer\Adapter\Php\InMemory;

use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\IndexWriter;
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

    public function write(): IndexWriter
    {
        return new InMemoryWriter($this->repository);
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
}
