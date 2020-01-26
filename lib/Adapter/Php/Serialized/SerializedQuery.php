<?php

namespace Phpactor\ProjectQuery\Adapter\Php\Serialized;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\ProjectQuery\Model\IndexQuery;
use Phpactor\ProjectQuery\Model\Record\ClassRecord;

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

    public function class(FullyQualifiedName $name): ?ClassRecord
    {
        return $this->repository->getClass($name);
    }
}
