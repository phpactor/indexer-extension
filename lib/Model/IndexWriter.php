<?php

namespace Phpactor\WorkspaceQuery\Model;

use Phpactor\WorkspaceQuery\Model\Record\ClassRecord;

interface IndexWriter
{
    public function class(ClassRecord $class): void;

    public function timestamp(): void;
}
