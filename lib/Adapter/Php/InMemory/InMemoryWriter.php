<?php

namespace Phpactor\WorkspaceQuery\Adapter\Php\InMemory;

use Phpactor\WorkspaceQuery\Model\IndexWriter;
use Phpactor\WorkspaceQuery\Model\Record\ClassRecord;

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
    }
}
