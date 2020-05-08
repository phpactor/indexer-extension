<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Statement\TraitDeclaration;
use Phpactor\Indexer\Model\Index;
use SplFileInfo;

class TraitDeclarationIndexer extends AbstractClassLikeIndexer
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
