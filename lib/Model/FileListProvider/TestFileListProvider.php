<?php

namespace Phpactor\Indexer\Model\FileListProvider;

use Phpactor\Indexer\Model\FileList;
use Phpactor\Indexer\Model\FileListProvider;
use Phpactor\Indexer\Model\Index;

class TestFileListProvider implements FileListProvider
{
    public function __construct
    public function provideFileList(Index $index, ?string $subPath = null): FileList
    {
    }
}
