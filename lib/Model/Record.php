<?php

namespace Phpactor\Indexer\Model;

abstract class Record
{
    /**
     * @var int
     */
    protected $lastModified;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * Return string which is unique to this record (used for namespacing),
     * e.g. "class".
     */
    abstract public function recordType(): string;

    public function setFilePath(string $path): self
    {
        $this->filePath = $path;
        return $this;
    }

    public function setLastModified(int $mtime): self
    {
        $this->lastModified = $mtime;
        return $this;
    }

    public function lastModified(): int
    {
        return $this->lastModified;
    }

    public function filePath(): ?string
    {
        return $this->filePath;
    }

    abstract public function identifier(): string;
}
