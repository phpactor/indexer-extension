<?php

namespace Phpactor\Indexer\Model;

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
        foreach ($this->fileList as $fileInfo) {
            $this->indexBuilder->index($fileInfo);
            yield $fileInfo->getPathname();
        }
    }

    public function run(): void
    {
        iterator_to_array($this->generator());
    }

    public function size(): int
    {
        return $this->fileList->count();
    }
}
