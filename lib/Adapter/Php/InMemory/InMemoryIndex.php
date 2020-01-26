<?php

namespace Phpactor\ProjectQuery\Adapter\Php\InMemory;

use DateTimeImmutable;
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

    public function __construct(InMemoryRepository $repository = null)
    {
        $this->repository = $repository;
    }

    public function lastUpdate(): int
    {
        return 0;
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
        $this->repository->reset();
    }
}
