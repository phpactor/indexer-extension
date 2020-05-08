<?php

namespace Phpactor\Indexer\Adapter\Tolerant;

use Microsoft\PhpParser\Node;
use SplFileInfo;
use Phpactor\Indexer\Model\Index;

interface TolerantIndexer
{
    public function canIndex(Node $node): bool;

    public function index(Index $index, SplFileInfo $info, Node $node): void;

    public function beforeParse(Index $index, SplFileInfo $info): void;
}
