<?php

namespace Phpactor\ProjectQuery\Model;

use Phpactor\ProjectQuery\Model\Record\ClassRecord;

interface IndexWriter
{
    public function class(ClassRecord $classIndex): void;
}
