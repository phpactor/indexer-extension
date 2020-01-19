<?php

namespace Phpactor\ProjectQuery\Adapter\Php;

use Phpactor\ProjectQuery\Model\IndexWriter;
use Phpactor\ProjectQuery\Model\Record\ClassRecord;

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
