<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\Record;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;

class ClassRecord extends Record
{
    /**
     * @var array<string>
     */
    private $implementations = [];

    /**
     * @var array<string>
     */
    private $implemented = [];

    public static function fromName(string $name): self
    {
        return new self(FullyQualifiedName::fromString($name));
    }

    public function clearImplements(): void
    {
        $this->implemented = [];
    }

    public function addImplementation($fqn): void
    {
        // TODO: Remove
        if ($fqn instanceof ReflectionClassLike) {
            $this->implementations[$fqn->name()->full()] = $fqn->name()->full();
            return;
        }
        $this->implementations[(string)$fqn] = (string)$fqn;
    }

    public function addImplements($fqn): void
    {
        // TODO: Remove
        if ($fqn instanceof ReflectionClassLike) {
            $this->implemented[$fqn->name()->full()] = $fqn->name()->full();
            return;
        }
        $this->implemented[(string)$fqn] = (string)$fqn;
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

    public function removeImplementation(FullyQualifiedName $name): bool
    {
        if (!isset($this->implementations[(string)$name])) {
            return false;
        }
        unset($this->implementations[(string)$name]);
        return true;
    }
}
