<?php

namespace Phpactor\Indexer\Model;

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