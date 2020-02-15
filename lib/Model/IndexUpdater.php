<?php

namespace Phpactor\WorkspaceQuery\Model;

interface IndexUpdater
{
    public function build(FileList $fileList): void;
}
