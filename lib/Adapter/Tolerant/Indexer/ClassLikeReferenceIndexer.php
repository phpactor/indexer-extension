<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\QualifiedName;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Indexer\Model\Index;
use Phpactor\TextDocument\Location;
use SplFileInfo;

class ClassLikeReferenceIndexer extends AbstractClassLikeIndexer
{
    public function canIndex(Node $node): bool
    {
        return $node instanceof QualifiedName;
    }

    public function beforeParse(Index $index, SplFileInfo $info): void
    {
    }

    public function index(Index $index, SplFileInfo $info, Node $node): void
    {
        $targetRecord = $this->getClassLikeRecord('class', $node, $index, $info);

        if (null === $targetRecord) {
            return;
        }

        if (false === $index->hasModified($targetRecord)) {
            $targetRecord->removeReferences();
        }

        $targetRecord->addReference(Location::fromPathAndOffset($info->getPathname(), $node->getStart()));
        $index->write($targetRecord);
    }
}
