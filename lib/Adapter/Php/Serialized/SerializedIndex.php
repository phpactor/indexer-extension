<?php

namespace Phpactor\ProjectQuery\Adapter\Php\Serialized;

use DateTimeImmutable;
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

    public function lastUpdate(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    public function query(): IndexQuery
    {
        return new SerializedQuery($this->repository);
    }

    public function write(): IndexWriter
    {
        return new SerializedWriter($this->repository);
    }
}
