<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\TextDocument\ByteOffset;
use Phpactor\Name\FullyQualifiedName;

trait FullyQualifiedReferenceTrait
{
    /**
     * @var string
     */
    private $fqn;

    /**
     * @var int
     */
    private $start;

    /**
     * @var array<string,bool>
     */
    private $references = [];

    public function __construct(
        FullyQualifiedName $fqn
    ) {
        // this object is serialized, do not store the object representation as
        // it adds around 100b to the size of each indexed class
        $this->fqn = $fqn->__toString();
    }

    public function setStart(ByteOffset $start): self
    {
        $this->start = $start->toInt();
        return $this;
    }

    public function fqn(): FullyQualifiedName
    {
        return FullyQualifiedName::fromString($this->fqn);
    }

    public function start(): ByteOffset
    {
        return ByteOffset::fromInt($this->start);
    }

    public function identifier(): string
    {
        return $this->fqn;
    }

    public function addReference(string $path): self
    {
        $this->references[$path] = true;

        return $this;
    }

    public function removeReference(string $path): self
    {
        if (!isset($this->references[$path])) {
            return $this;
        }

        unset($this->references[$path]);

        return $this;
    }

    /**
     * @return array<string>
     */
    public function references(): array
    {
        return array_keys($this->references);
    }
}
