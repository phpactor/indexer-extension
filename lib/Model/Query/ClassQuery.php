<?php

namespace Phpactor\Indexer\Model\Query;

use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Name\FullyQualifiedName;

class ClassQuery implements IndexQuery
{
    /**
     * @var Index
     */
    private $index;

    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    public function get(string $identifier): ?ClassRecord
    {
        return $this->index->get(ClassRecord::fromName($identifier));
    }

    /**
     * @return array<FullyQualifiedName>
     */
    public function implementing(string $name): array
    {
        $record = $this->index->get(ClassRecord::fromName($name));
        assert($record instanceof ClassRecord);

        return array_map(function (string $fqn) {
            return FullyQualifiedName::fromString($fqn);
        }, $record->implementations());
    }
}
