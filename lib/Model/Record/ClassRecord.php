<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\Record;
use Phpactor\Name\FullyQualifiedName;

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

    public function addImplementation(FullyQualifiedName $fqn): void
    {
        $this->implementations[(string)$fqn] = (string)$fqn;
    }

    public function addImplements(FullyQualifiedName $fqn): void
    {
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
