<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;

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
     * @var array<string>
     */
    private $implementations = [];

    /**
     * @var array<string>
     */
    private $implemented = [];

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

    public function addImplementation(ReflectionClassLike $implementation): void
    {
        $this->implementations[$implementation->name()->full()] = $implementation->name()->full();
    }

    public function addImplements(ReflectionClassLike $implementedClass): void
    {
        $this->implemented[$implementedClass->name()->full()] = $implementedClass->name()->full();
    }

    public function removeClass(FullyQualifiedName $implementedClass): void
    {
        foreach ($this->implementations as $key => $implementation) {
            if ($implementation !== $implementedClass->__toString()) {
                continue;
            }

            unset($this->implementations[$key]);
        }
    }

    public function fqn(): FullyQualifiedName
    {
        return $this->fqn;
    }

    /**
     * @return array<string>
     */
    public function implementations(): array
    {
        return $this->implementations;
    }

    /**
     * @return array<string>
     */
    public function implementedClasses(): array
    {
        return $this->implemented;
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
