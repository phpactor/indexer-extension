<?php

namespace Phpactor\WorkspaceQuery\Model\Record;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;

class ClassRecord
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
     * @var string
     */
    private $filePath;

    /**
     * @var ByteOffset
     */
    private $start;

    public function __construct(
        int $lastModified,
        FullyQualifiedName $fqn,
        string $type,
        ByteOffset $start,
        string $filePath
    ) {
        $this->lastModified = $lastModified;
        $this->type = $type;
        $this->fqn = $fqn;
        $this->filePath = $filePath;
        $this->start = $start;
    }

    public function addImplementation(ReflectionClassLike $implementation): void
    {
        $this->implementations[$implementation->name()->full()] = $implementation->name()->full();
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

    public function lastModified(): int
    {
        return $this->lastModified;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function start(): ByteOffset
    {
        return $this->start;
    }
}
