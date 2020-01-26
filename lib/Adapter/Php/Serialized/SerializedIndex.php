<?php

namespace Phpactor\ProjectQuery\Adapter\Php\Serialized;

use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\ProjectQuery\Model\Index;
use Phpactor\ProjectQuery\Model\IndexQuery;
use Phpactor\ProjectQuery\Model\IndexWriter;

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

    public function isFresh(FilePath $fileInfo): bool
    {
        $mtime = filemtime($fileInfo->path());

        return $mtime < $this->lastUpdate();
    }

    public function reset(): void
    {
        $this->repository->reset();
    }
}
