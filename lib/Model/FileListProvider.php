<?php

namespace Phpactor\WorkspaceQuery\Model;

interface FileListProvider
{
    public function provideFileList(Index $index, ?string $subPath = null): FileList;
}
