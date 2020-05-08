<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Indexer\Model\Exception\CorruptedRecord;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\TextDocument\ByteOffset;

abstract class Record
{
    /**
     * @var int
     */
    private $lastModified;

    /**
     * @var string
     */
    private $filePath;

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

    public function __wakeup(): void
    {
        if (null === $this->lastModified) {
            throw new CorruptedRecord(sprintf(
                'Record was corrupted'
            ));
        }
    }

    abstract public function identifier(): string;
}
