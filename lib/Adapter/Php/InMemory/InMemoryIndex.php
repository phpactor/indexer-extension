<?php

namespace Phpactor\ProjectQuery\Adapter\Php\InMemory;

use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\ProjectQuery\Model\Index;
use Phpactor\ProjectQuery\Model\IndexQuery;
use Phpactor\ProjectQuery\Model\IndexWriter;

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

    public function __construct(InMemoryRepository $repository = null)
    {
        $this->repository = $repository;
        $this->lastUpdate = time();
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

    public function isFresh(FilePath $fileInfo): bool
    {
        return false;
    }

    public function reset(): void
    {
        $this->lastUpdate = 0;
        $this->repository->reset();
    }
}
