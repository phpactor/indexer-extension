<?php

namespace Phpactor\Indexer\Adapter\Php\InMemory;

use Generator;
use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\RecordFactory;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\SearchIndex;

class InMemorySearchIndex implements SearchIndex
{
    /**
     * @var array<string,array{string,string}>
     */
    private $buffer = [];

    /**
     * @return Generator<Record>
     */
    public function search(string $query): Generator
    {
        foreach ($this->buffer as [$recordType, $identifier]) {
            if (!preg_match('{' . $query. '}', $identifier)) {
                continue;
            }

            yield RecordFactory::create($recordType, $identifier);
        }
    }

    public function write(Record $record): void
    {
        $this->buffer[$record->identifier()] = [$record->recordType(), $record->identifier()];
    }

    public function flush(): void
    {
    }

    public function remove(Record $record): void
    {
        unset($this->buffer[$record->identifier()]);
    }

    public function has(ClassRecord $record): bool
    {
        return isset($this->buffer[$record->identifier()]);
    }
}
