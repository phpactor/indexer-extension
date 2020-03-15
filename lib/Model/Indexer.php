<?php

namespace Phpactor\Indexer\Model;

class Indexer
{
    /**
     * @var IndexBuilder
     */
    private $builder;

    /**
     * @var Index
     */
    private $index;

    /**
     * @var FileListProvider
     */
    private $provider;

    public function __construct(IndexBuilder $builder, Index $index, FileListProvider $provider)
    {
        $this->builder = $builder;
        $this->index = $index;
        $this->provider = $provider;
    }

    public function getJob(?string $subPath = null): IndexJob
    {
        return new IndexJob(
            $this->builder,
            $this->provider->provideFileList($this->index, $subPath)
        );
    }

    public function reset(): void
    {
        $this->index->reset();
    }
}
