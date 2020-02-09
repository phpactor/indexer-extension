<?php

namespace Phpactor\WorkspaceQuery\Model;

use Generator;

interface IndexBuilder
{
    /**
     * @return Generator<string>
     */
    public function buildGenerator(?string $subPath = null): Generator;

    public function build(?string $subPath = null): void;

    public function size(): int;
}
