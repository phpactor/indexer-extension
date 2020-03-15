<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Indexer\Model\Record\ClassRecord;

interface IndexWriter
{
    public function class(ClassRecord $class): void;

    public function timestamp(): void;
}
