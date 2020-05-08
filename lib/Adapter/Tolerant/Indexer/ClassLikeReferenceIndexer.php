<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\QualifiedName;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FileRecord;
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
        $fileRecord = $index->get(FileRecord::fromFileInfo($info));
        assert($fileRecord instanceof FileRecord);

        foreach ($fileRecord->references() as $outgoingReference) {
            if ($outgoingReference->type() !== ClassRecord::RECORD_TYPE) {
                continue;
            }

            $record = $index->get(ClassRecord::fromName($outgoingReference->identifier()));
            assert($record instanceof ClassRecord);
            $record->removeReference($fileRecord->identifier());
            $index->write($record);
        }
    }

    public function index(Index $index, SplFileInfo $info, Node $node): void
    {
        $targetRecord = $this->getClassLikeRecord('class', $node, $index, $info);
        $targetRecord->addReference($info->getPathname());
        $index->write($targetRecord);

        $fileRecord = $index->get(FileRecord::fromFileInfo($info));
        assert($fileRecord instanceof FileRecord);
        $fileRecord->addReference(new RecordReference('class', $targetRecord->identifier(), $node->getStart()));
        $index->write($fileRecord);
    }
}
