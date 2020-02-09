<?php

namespace Phpactor\WorkspaceQuery\Adapter\Php\Serialized;

use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\WorkspaceQuery\Model\Index;
use Phpactor\WorkspaceQuery\Model\IndexQuery;
use Phpactor\WorkspaceQuery\Model\IndexWriter;

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

    public function exists(): bool
    {
        return $this->repository->lastUpdate() > 0;
    }
}
