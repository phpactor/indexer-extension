<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Microsoft\PhpParser\Node\Statement\TraitDeclaration;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Index;
use SplFileInfo;

class TraitDeclarationIndexer extends ClassLikeIndexer
{
    public function canIndex(Node $node): bool
    {
        return $node instanceof TraitDeclaration;
    }

    public function index(Index $index, SplFileInfo $info, Node $node): void
    {
        assert($node instanceof TraitDeclaration);
        $record = $this->getClassLikeRecord('trait', $node, $index, $info);
        $index->write($record);
    }
}
