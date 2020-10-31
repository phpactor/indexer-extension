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
        $this->indexBuilder->done();
    }

    public function run(): void
    {
        /** @phpstan-ignore-next-line */
        iterator_to_array($this->generator());
    }

    public function size(): int
    {
        return $this->fileList->count();
    }
}
