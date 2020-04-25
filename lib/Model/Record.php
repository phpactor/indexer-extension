<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\TextDocument\ByteOffset;

abstract class Record
{
    /**
     * @var int
     */
    private $lastModified;

    /**
     * @var FullyQualifiedName
     */
    private $fqn;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var ByteOffset
     */
    private $start;

    public function __construct(
        FullyQualifiedName $fqn
    ) {
        $this->fqn = $fqn;
    }

    public function setFilePath(string $path): self
    {
        $this->filePath = $path;
        return $this;
    }

    public function setStart(ByteOffset $start): self
    {
        $this->start = $start;
        return $this;
    }

    public function setLastModified(int $mtime): self
    {
        $this->lastModified = $mtime;
        return $this;
    }

    public function fqn(): FullyQualifiedName
    {
        return $this->fqn;
    }

    public function lastModified(): int
    {
        return $this->lastModified;
    }

    public function filePath(): ?string
    {
        return $this->filePath;
    }

    public function start(): ByteOffset
    {
        return $this->start;
    }
}
