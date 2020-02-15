<?php

namespace Phpactor\WorkspaceQuery\Adapter\Filesystem;

use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\Filesystem\Domain\FilesystemRegistry;
use Phpactor\WorkspaceQuery\Model\FileList;
use Phpactor\WorkspaceQuery\Model\FileListProvider;
use SplFileInfo;
use Phpactor\WorkspaceQuery\Model\Index;

class FilesystemFileListProvider implements FileListProvider
{
    /**
     * @var FilesystemRegistry
     */
    private $filesystemRegistry;

    /**
     * @var string
     */
    private $filesystemName;


    public function __construct(FilesystemRegistry $filesystemRegistry, string $filesystemName)
    {
        $this->filesystemRegistry = $filesystemRegistry;
        $this->filesystemName = $filesystemName;
    }

    public function provideFileList(Index $index, ?string $subPath = null): FileList
    {
        $filesystem = $this->filesystemRegistry->get($this->filesystemName);
        $files = $filesystem->fileList()->phpFiles();
        if ($subPath) {
            $files = $files->within(FilePath::fromString($subPath));
        }

        return FileList::fromInfoIterator($files->filter(function (SplFileInfo $fileInfo) use ($index) {
            return false === $index->isFresh($fileInfo);
        })->getIterator());
    }
}
