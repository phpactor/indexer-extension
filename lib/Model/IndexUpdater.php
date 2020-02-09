<?php

namespace Phpactor\WorkspaceQuery\Model;

use Generator;

interface IndexUpdater
{
    public function build(?string $subPath = null): void;
}
