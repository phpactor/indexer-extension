<?php

namespace Phpactor\ProjectQuery\Adapter\Php;

use DateTimeImmutable;
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
        $this->repository = $repository ?: new InMemoryRepository();
    }

    public function lastUpdate(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
    public function query(): IndexQuery
    {
        return new InMemoryQuery($this->repository);
    }

    public function write(): IndexWriter
    {
        return new InMemoryWriter($this->repository);
    }
}
