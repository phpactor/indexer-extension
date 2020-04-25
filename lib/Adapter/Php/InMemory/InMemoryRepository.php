<?php

namespace Phpactor\Indexer\Adapter\Php\InMemory;

use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\Record\FunctionRecord;

class InMemoryRepository
{
    /**
     * @var array<ClassRecord>
     */
    private $classes = [];

    /**
     * @var array<FunctionRecord>
     */
    private $functions = [];

    /**
     * @var int
     */
    public $lastUpdate = 0;

    public function putClass(Record $class): void
    {
        $this->classes[$class->fqn()->__toString()] = $class;
    }

    public function putFunction(FunctionRecord $function): void
    {
        $this->functions[$function->fqn()->__toString()] = $function;
    }

    public function getClass(string $fqn): ?Record
    {
        if (!isset($this->classes[$fqn])) {
            return null;
        }

        return $this->classes[$fqn];
    }

    public function reset(): void
    {
        $this->classes = [];
        $this->functions = [];
    }

    public function getFunction(string $fqn): ?FunctionRecord
    {
        if (!isset($this->functions[$fqn])) {
            return null;
        }

        return $this->functions[$fqn];
    }
}
