<?php

namespace Phpactor\WorkspaceQuery\Model;

use Phpactor\Filesystem\Domain\FilePath;

interface Index
{
    public function lastUpdate(): int;

    public function query(): IndexQuery;

    public function write(): IndexWriter;

    public function isFresh(FilePath $fileInfo): bool;

    public function reset(): void;

    public function exists(): bool;
}
