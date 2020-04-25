<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\Record\FunctionRecord;

interface IndexWriter
{
    public function class(Record $class): void;

    public function timestamp(): void;

    public function function(FunctionRecord $function): void;
}
