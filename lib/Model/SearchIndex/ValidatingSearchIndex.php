<?php

namespace Phpactor\Indexer\Model\SearchIndex;

use Generator;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\Record\HasPath;
use Phpactor\Indexer\Model\SearchIndex;

class ValidatingSearchIndex implements SearchIndex
{
    /**
     * @var SearchIndex
     */
    private $innerIndex;

    /**
     * @var Index
     */
    private $index;

    public function __construct(SearchIndex $innerIndex, Index $index)
    {
        $this->innerIndex = $innerIndex;
        $this->index = $index;
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $query): Generator
    {
        foreach ($this->innerIndex->search($query) as $result) {
            if (!$this->index->has($result)) {
                $this->innerIndex->remove($result);
                continue;
            }

            $record = $this->index->get($result);

            if (!$record instanceof HasPath) {
                yield $result;
                return;
            }

            if (!file_exists($record->filePath())) {
                $this->innerIndex->remove($record);
                continue;
            }

            yield $result;
        }
    }

    public function write(Record $record): void
    {
        $this->innerIndex->write($record);
    }

    public function remove(Record $record): void
    {
        $this->innerIndex->remove($record);
    }

    public function flush(): void
    {
        $this->innerIndex->flush();
    }
}
