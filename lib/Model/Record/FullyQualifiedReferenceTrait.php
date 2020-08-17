<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\Exception\CorruptedRecord;
use Phpactor\Indexer\Model\Name\FullyQualifiedName;
use Phpactor\TextDocument\ByteOffset;

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

    public function __wakeup(): void
    {
        if (null === $this->fqn) {
            throw new CorruptedRecord(sprintf(
                'Record was corrupted'
            ));
        }
    }
}
