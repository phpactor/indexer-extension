<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Indexer\Model\Record\ClassRecord;
use SplFileInfo;

interface Index
{
    public function lastUpdate(): int;

    public function query(): IndexQuery;

    public function write(Record $record): void;

    public function isFresh(SplFileInfo $fileInfo): bool;

    public function reset(): void;

    public function exists(): bool;

    public function updateTimestamp(): void;

    /**
     * Return the indexed version of Record, if it doesn't exist in the index,
     * it should return the given record.
     *
     * If the record is of an unknown type (e.g. not ClassRecord or FunctionRecord)
     * then an exception will be thrown.
     * 
     * @throws \RuntimeException
     *
     * @template TRecord of \Phpactor\Indexer\Model\Record
     * @param TRecord $record
     *
     * @return TRecord
     */
    public function get(Record $record): Record;
}
