<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\Name\Name;

class MemberReference
{
    /**
     * @var string
     */
    private $type;
    /**
     * @var FullyQualifiedName
     */
    private $name;

    /**
     * @var string
     */
    private $memberName;

    public function __construct(string $type, FullyQualifiedName $name, string $memberName)
    {
        $this->type = $type;
        $this->name = $name;
        $this->memberName = $memberName;
    }

    public static function create(string $type, string $containerFqn, string $memberName): self
    {
        return new self($type, FullyQualifiedName::fromString($containerFqn), $memberName);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function containerFqn(): FullyQualifiedName
    {
        return $this->name;
    }

    public function memberName(): string
    {
        return $this->memberName;
    }
}
