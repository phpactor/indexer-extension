<?php

namespace Phpactor\WorkspaceQuery\Model;

use Generator;

interface IndexBuilder extends IndexUpdater
{
    /**
     * @return Generator<string>
     */
    public function index(FileList $fileList): Generator;
}
