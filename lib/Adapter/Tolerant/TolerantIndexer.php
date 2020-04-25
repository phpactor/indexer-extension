<?php

namespace Phpactor\Indexer\Adapter\Tolerant;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use SplFileInfo;
use Phpactor\Indexer\Model\Index;

interface TolerantIndexer
{
    public function canIndex(Node $node): bool;

    public function index(Index $index, SplFileInfo $info, Node $node): void;
}
