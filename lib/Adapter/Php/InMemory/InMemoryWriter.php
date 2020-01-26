<?php

namespace Phpactor\ProjectQuery\Adapter\Php\InMemory;

use Phpactor\ProjectQuery\Model\IndexWriter;
use Phpactor\ProjectQuery\Model\Record\ClassRecord;
use Phpactor\ProjectQuery\Adapter\Php\InMemory\InMemoryRepository;

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
}
