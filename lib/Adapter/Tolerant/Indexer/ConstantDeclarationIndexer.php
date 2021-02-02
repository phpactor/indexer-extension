<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\ConstElement;
use Microsoft\PhpParser\Node\DelimitedList\ConstElementList;
use Microsoft\PhpParser\Node\Statement\ConstDeclaration;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Indexer\Model\Record\ConstantRecord;
use Phpactor\TextDocument\ByteOffset;
use SplFileInfo;
use Phpactor\Indexer\Model\Index;

class ConstantDeclarationIndexer implements TolerantIndexer
{
    public function canIndex(Node $node): bool
    {
        return $node instanceof ConstDeclaration;
    }

    public function index(Index $index, SplFileInfo $info, Node $node): void
    {
        assert($node instanceof ConstDeclaration);
        if (!$node->constElements instanceof ConstElementList) {
            return;
        }
        foreach ($node->constElements->getChildNodes() as $constNode) {
            assert($constNode instanceof ConstElement);
            $record = $index->get(ConstantRecord::fromName($constNode->getNamespacedName()->getFullyQualifiedNameText()));
            assert($record instanceof ConstantRecord);
            $record->setStart(ByteOffset::fromInt($node->getStart()));
            $record->setFilePath($info->getPathname());
            $index->write($record);
        }
    }

    public function beforeParse(Index $index, SplFileInfo $info): void
    {
    }
}
