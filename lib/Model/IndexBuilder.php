<?php

namespace Phpactor\WorkspaceQuery\Model;

use Generator;

interface IndexBuilder extends IndexUpdater
{
    /**
     * @return Generator<string>
     */
    public function buildGenerator(?string $subPath = null): Generator;

    public function size(): int;
}
