<?php

namespace Phpactor\Indexer\Adapter\Filesystem;

use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\Filesystem\Domain\FilesystemRegistry;
use Phpactor\Indexer\Model\FileList;
use Phpactor\Indexer\Model\FileListProvider;
use SplFileInfo;
use Phpactor\Indexer\Model\Index;

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

        if (null !== $subPath && $filesystem->exists($subPath) && is_file($subPath)) {
            return FileList::fromSingleFilePath($subPath);
        }

        $files = $filesystem->fileList()->phpFiles();

        if ($subPath) {
            $files = $files->within(FilePath::fromString($subPath));
        }

        if (!$subPath) {
            $files = $files->filter(function (SplFileInfo $fileInfo) use ($index) {
                return false === $index->isFresh($fileInfo);
            });
        }

        return FileList::fromInfoIterator($files->getIterator());
    }
}
