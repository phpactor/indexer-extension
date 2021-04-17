<?php

namespace Phpactor\Indexer\Model\FileListProvider;

use Generator;
use Phpactor\Indexer\Model\DirtyDocumentTracker;
use Phpactor\Indexer\Model\FileList;
use Phpactor\Indexer\Model\FileListProvider;
use Phpactor\Indexer\Model\Index;
use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentUri;
use SplFileInfo;

class DirtyFileListProvider implements FileListProvider, DirtyDocumentTracker
{
    /**
     * @var string
     */
    private $dirtyPath;

    public function __construct(string $dirtyPath)
    {
        $this->dirtyPath = $dirtyPath;
    }

    public function markDirty(TextDocumentUri $uri): void
    {
        $handle = fopen($this->dirtyPath, 'a');
        fwrite($handle, $uri->path() . "\n");
        fclose($handle);
    }

    public function provideFileList(Index $index, ?string $subPath = null): FileList
    {
        return FileList::fromInfoIterator($this->paths());
    }

    private function paths(): Generator
    {
        $contents = @file_get_contents($this->dirtyPath);
        if (false === $contents) {
            return;
        }

        $paths = explode("\n", $contents);
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }
            yield new SplFileInfo($path);
        }

        unlink($this->dirtyPath);
    }
}
