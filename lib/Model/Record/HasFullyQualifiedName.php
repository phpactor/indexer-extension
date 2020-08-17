<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\TextDocument\ByteOffset;

interface HasFullyQualifiedName
{
    public function fqn(): FullyQualifiedName;
}
