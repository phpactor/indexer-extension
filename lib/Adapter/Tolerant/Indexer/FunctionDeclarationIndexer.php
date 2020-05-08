<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Statement\FunctionDeclaration;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Indexer\Model\Index;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use SplFileInfo;

class FunctionDeclarationIndexer implements TolerantIndexer
{
    public function canIndex(Node $node): bool
    {
        return $node instanceof FunctionDeclaration;
    }

    public function index(Index $index, SplFileInfo $info, Node $node): void
    {
        assert($node instanceof FunctionDeclaration);
        $record = $index->get(FunctionRecord::fromName($node->getNamespacedName()->getFullyQualifiedNameText()));
        assert($record instanceof FunctionRecord);
        $record->setLastModified($info->getCTime());
        $record->setStart(ByteOffset::fromInt($node->getStart()));
        $record->setFilePath($info->getPathname());
        $index->write($record);
    }

    public function beforeParse(Index $index, SplFileInfo $info): void
    {
    }
}
