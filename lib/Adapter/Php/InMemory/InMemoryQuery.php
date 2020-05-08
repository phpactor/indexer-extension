<?php

namespace Phpactor\Indexer\Adapter\Php\InMemory;

use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\IndexQuery;

class InMemoryQuery implements IndexQuery
{
    /**
     * @var InMemoryRepository
     */
    private $repository;

    public function __construct(InMemoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function implementing(FullyQualifiedName $name): array
    {
        $class = $this->repository->getClass($name->__toString());

        if (!$class) {
            return [];
        }

        return array_map(function (string $fqn) {
            return FullyQualifiedName::fromString($fqn);
        }, $class->implementations());
    }

    public function class(FullyQualifiedName $name): ?ClassRecord
    {
        return $this->repository->getClass($name->__toString());
    }

    public function function(FullyQualifiedName $name): ?FunctionRecord
    {
        return $this->repository->getFunction($name->__toString());
    }

    public function file(string $path): ?FileRecord
    {
    }
}
