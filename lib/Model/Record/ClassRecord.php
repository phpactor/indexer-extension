<?php

namespace Phpactor\ProjectQuery\Model\Record;

use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;

class ClassRecord
{
    /**
     * @var int
     */
    private $lastModified;

    /**
     * @var string
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

    public function __construct(int $lastModified, string $fqn, string $type)
    {
        $this->lastModified = $lastModified;
        $this->fqn = $fqn;
        $this->type = $type;
    }

    public function addImplementation(ReflectionClassLike $implementation): void
    {
        $this->implementations[$implementation->name()->full()] = $implementation->name()->full();
    }

    public function fqn(): string
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
}
