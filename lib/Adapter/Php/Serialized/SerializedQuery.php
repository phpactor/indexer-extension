<?php

namespace Phpactor\Indexer\Adapter\Php\Serialized;

use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\Record;

class SerializedQuery implements IndexQuery
{
    /**
     * @var FileRepository
     */
    private $repository;

    public function __construct(FileRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritDoc}
     */
    public function implementing(FullyQualifiedName $name): array
    {
        $class = $this->repository->getClass($name);

        if (!$class) {
            return [];
        }

        return array_map(function (string $fqn) {
            return FullyQualifiedName::fromString($fqn);
        }, $class->implementations());
    }

    public function class(FullyQualifiedName $name): ?Record
    {
        return $this->repository->getClass($name);
    }

    public function function(FullyQualifiedName $fullyQualifiedName): ?FunctionRecord
    {
        return $this->repository->getFunction($fullyQualifiedName);
    }
}
