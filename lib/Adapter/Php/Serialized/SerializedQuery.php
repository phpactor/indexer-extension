<?php

namespace Phpactor\Indexer\Adapter\Php\Serialized;

use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\IndexQuery;

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
        $record = $this->repository->get(ClassRecord::fromName($name));

        if (!$record) {
            return [];
        }

        return array_map(function (string $fqn) {
            return FullyQualifiedName::fromString($fqn);
        }, $record->implementations());
    }

    public function class(FullyQualifiedName $name): ?ClassRecord
    {
        return $this->repository->get(ClassRecord::fromName($name));
    }

    public function function(FullyQualifiedName $name): ?FunctionRecord
    {
        return $this->repository->get(FunctionRecord::fromName($name));
    }
}
