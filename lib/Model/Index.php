<?php

namespace Phpactor\Indexer\Model;

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
}
