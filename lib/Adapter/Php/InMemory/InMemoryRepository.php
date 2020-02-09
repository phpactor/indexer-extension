<?php

namespace Phpactor\WorkspaceQuery\Adapter\Php\InMemory;

use Phpactor\WorkspaceQuery\Model\Record\ClassRecord;

class InMemoryRepository
{
    /**
     * @var array<ClassRecord>
     */
    private $classes = [];

    public function putClass(ClassRecord $class): void
    {
        $this->classes[$class->fqn()->__toString()] = $class;
    }

    public function getClass(string $fqn): ?ClassRecord
    {
        if (!isset($this->classes[$fqn])) {
            return null;
        }

        return $this->classes[$fqn];
    }

    public function reset(): void
    {
        $this->classes = [];
    }
}
