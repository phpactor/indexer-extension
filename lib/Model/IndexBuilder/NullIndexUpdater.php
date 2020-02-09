<?php

namespace Phpactor\WorkspaceQuery\Model\IndexBuilder;

use Generator;
use Phpactor\WorkspaceQuery\Model\IndexUpdater;

class NullIndexUpdater implements IndexUpdater
{
    public function build(?string $subPath = null): void
    {
    }
}
