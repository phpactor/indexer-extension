<?php

namespace Phpactor\WorkspaceQuery\Model;

interface IndexUpdater
{
    /**
     * @return Generator<string>
     */
    public function build(FileList $fileList): void;
}
