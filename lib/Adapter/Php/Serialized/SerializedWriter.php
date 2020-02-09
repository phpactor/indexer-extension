<?php

namespace Phpactor\WorkspaceQuery\Adapter\Php\Serialized;

use Phpactor\WorkspaceQuery\Model\IndexWriter;
use Phpactor\WorkspaceQuery\Model\Record\ClassRecord;

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
