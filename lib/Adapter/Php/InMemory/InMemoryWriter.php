<?php

namespace Phpactor\Indexer\Adapter\Php\InMemory;

use Phpactor\Indexer\Model\IndexWriter;
use Phpactor\Indexer\Model\Record\ClassRecord;

class InMemoryWriter implements IndexWriter
{
    /**
     * @var InMemoryRepository
     */
    private $repository;

    public function __construct(InMemoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function class(ClassRecord $class): void
    {
        $this->repository->putClass($class);
    }

    public function timestamp(): void
    {
        $this->repository->lastUpdate = time();
    }
}
