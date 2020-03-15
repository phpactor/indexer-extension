<?php

namespace Phpactor\Indexer\Model;

use Generator;

interface IndexBuilder extends IndexUpdater
{
    /**
     * @return Generator<string>
     */
    public function index(FileList $fileList): Generator;
}
