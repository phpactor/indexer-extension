<?php

namespace Phpactor\WorkspaceQuery\Model;

interface IndexUpdater
{
    public function build(?string $subPath = null): void;
}
