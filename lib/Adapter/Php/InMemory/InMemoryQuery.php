<?php

namespace Phpactor\ProjectQuery\Adapter\Php\InMemory;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\ProjectQuery\Model\IndexQuery;
use Phpactor\ProjectQuery\Model\Record\ClassRecord;

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
}
