<?php

namespace Phpactor\Indexer\Adapter\Php\InMemory;

use Phpactor\Indexer\Model\IndexWriter;
use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;

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

    public function function(FunctionRecord $function): void
    {
        $this->repository->putFunction($function);
    }

    public function timestamp(): void
    {
        $this->repository->lastUpdate = time();
    }
}
