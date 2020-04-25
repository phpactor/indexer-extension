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
    private $type;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var ByteOffset
     */
    private $start;

    public function __construct(
        FullyQualifiedName $fqn,
        ?string $type = null,
        ?ByteOffset $start = null,
        ?string $filePath = null,
        ?int $lastModified = null
    ) {
        $this->lastModified = $lastModified;
        $this->type = $type;
        $this->fqn = $fqn;
        $this->filePath = $filePath;
        $this->start = $start;
    }

    public function withType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function withFilePath(string $path): self
    {
        $this->filePath = $path;
        return $this;
    }

    public function withStart(ByteOffset $start): self
    {
        $this->start = $start;
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

    public function type(): string
    {
        return $this->type;
    }

    public function filePath(): ?string
    {
        return $this->filePath;
    }

    public function start(): ByteOffset
    {
        return $this->start;
    }

    public function withLastModified(int $mtime): self
    {
        $this->lastModified = $mtime;
        return $this;
    }
}
