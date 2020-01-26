<?php

namespace Phpactor\ProjectQuery\Model;

use Phpactor\ProjectQuery\Model\Record\ClassRecord;

interface IndexWriter
{
    public function class(ClassRecord $class): void;

    public function timestamp(): void;
}
