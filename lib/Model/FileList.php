<?php

namespace Phpactor\Indexer\Model;

use AppendIterator;
use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;
use SplFileInfo;

/**
 * @implements \IteratorAggregate<SplFileInfo>
 */
class FileList implements IteratorAggregate, Countable
{
    /**
     * @var Iterator<SplFileInfo>
     */
    private $splFileInfos;

    /**
     * @param Iterator<SplFileInfo> $splFileInfos
     */
    public function __construct(Iterator $splFileInfos)
    {
        $this->splFileInfos = new ArrayIterator(iterator_to_array($splFileInfos));
    }

    public static function empty(): self
    {
        return new self(new ArrayIterator([]));
    }

    /**
     * @param Iterator<SplFileInfo> $splFileInfos
     */
    public static function fromInfoIterator(Iterator $splFileInfos): self
    {
        return new self($splFileInfos);
    }

    public static function fromSingleFilePath(?string $subPath): self
    {
        return new self(new ArrayIterator([ new SplFileInfo($subPath) ]));
    }

    public function merge(FileList $fileList): self
    {
        $iterator = new AppendIterator();
        $iterator->append($this->splFileInfos);
        $iterator->append($fileList->splFileInfos);

        return new self($iterator);
    }

    /**
     * @return Iterator<SplFileInfo>
     */
    public function getIterator(): Iterator
    {
        return $this->splFileInfos;
    }

    public function count(): int
    {
        return count(iterator_to_array($this->splFileInfos));
    }
}
