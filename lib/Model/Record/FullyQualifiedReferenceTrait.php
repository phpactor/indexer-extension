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
}
