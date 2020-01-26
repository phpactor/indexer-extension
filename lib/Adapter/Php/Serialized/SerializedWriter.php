<?php

namespace Phpactor\ProjectQuery\Adapter\Php\Serialized;

use Phpactor\ProjectQuery\Model\IndexWriter;
use Phpactor\ProjectQuery\Model\Record\ClassRecord;
use RuntimeException;

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
}
