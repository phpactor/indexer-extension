<?php

namespace Phpactor\WorkspaceQuery\Model;

use Generator;

class IndexJob
{
    /**
     * @var IndexBuilder
     */
    private $indexBuilder;

    /**
     * @var FileList
     */
    private $fileList;

    public function __construct(IndexBuilder $indexBuilder, FileList $fileList)
    {
        $this->indexBuilder = $indexBuilder;
        $this->fileList = $fileList;
    }

    /**
     * @return Generator<string>
     */
    public function generator(): Generator
    {
        foreach ($this->indexBuilder->index($this->fileList) as $filePath) {
            yield $filePath;
        }
    }

    public function size(): int
    {
        return $this->fileList->count();
    }
}
