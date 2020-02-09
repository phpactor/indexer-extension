<?php

namespace Phpactor\WorkspaceQuery\Model\IndexBuilder;

use Phpactor\WorkspaceQuery\Model\IndexUpdater;

class NullIndexUpdater implements IndexUpdater
{
    public function build(?string $subPath = null): void
    {
    }
}
