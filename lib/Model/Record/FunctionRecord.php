<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\TextDocument\ByteOffset;

class FunctionRecord
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $filePath;
    /**
     * @var ByteOffset
     */
    private $start;

    /**
     * @var int
     */
    private $lastModified;

    /**
     * @var FullyQualifiedName
     */
    private $fqn;

    public function __construct(
        int $lastModified,
        FullyQualifiedName $fqn,
        string $filePath,
        ByteOffset $start
    ) {
        $this->filePath = $filePath;
        $this->start = $start;
        $this->lastModified = $lastModified;
        $this->fqn = $fqn;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function start(): ByteOffset
    {
        return $this->start;
    }

    public function lastModified(): int
    {
        return $this->lastModified;
    }

    public function fqn(): FullyQualifiedName
    {
        return $this->fqn;
    }
}
