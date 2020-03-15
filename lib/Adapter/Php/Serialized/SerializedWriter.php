<?php

namespace Phpactor\Indexer\Adapter\Php\Serialized;

use Phpactor\Indexer\Model\IndexWriter;
use Phpactor\Indexer\Model\Record\ClassRecord;

class SerializedWriter implements IndexWriter
{
    /**
     * @var FileRepository
     */
    private $repository;

    public function __construct(FileRepository $repository)
    {
        $this->repository = $repository;
    }

    public function class(ClassRecord $class): void
    {
        $this->repository->putClass($class);
    }

    public function timestamp(): void
    {
        $this->repository->putTimestamp();
    }
}
