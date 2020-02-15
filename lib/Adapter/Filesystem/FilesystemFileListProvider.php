<?php

namespace Phpactor\WorkspaceQuery\Adapter\Filesystem;

use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\Filesystem\Domain\Filesystem;
use Phpactor\WorkspaceQuery\Model\FileList;
use Phpactor\WorkspaceQuery\Model\FileListProvider;
use SplFileInfo;
use Phpactor\WorkspaceQuery\Model\Index;

class FilesystemFileListProvider implements FileListProvider
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function provideFileList(Index $index, ?string $subPath = null): FileList
    {
        $files = $this->filesystem->fileList()->phpFiles();
        if ($subPath) {
            $files = $files->within(FilePath::fromString($subPath));
        }

        return FileList::fromInfoIterator($files->filter(function (SplFileInfo $fileInfo) use ($index) {
            return false === $index->isFresh($fileInfo);
        })->getIterator());
    }
}
