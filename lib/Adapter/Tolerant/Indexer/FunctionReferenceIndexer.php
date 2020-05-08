<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\QualifiedName;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\TextDocument\ByteOffset;
use SplFileInfo;

class FunctionReferenceIndexer extends AbstractClassLikeIndexer
{
    public function canIndex(Node $node): bool
    {
        return $node instanceof QualifiedName && $node->parent instanceof CallExpression;
    }

    public function beforeParse(Index $index, SplFileInfo $info): void
    {
        $fileRecord = $index->get(FileRecord::fromFileInfo($info));
        assert($fileRecord instanceof FileRecord);

        foreach ($fileRecord->references() as $outgoingReference) {
            if ($outgoingReference->type() !== FunctionRecord::RECORD_TYPE) {
                continue;
            }

            $record = $index->get(FunctionRecord::fromName($outgoingReference->identifier()));
            assert($record instanceof FunctionRecord);
            $record->removeReference($fileRecord->identifier());
            $index->write($record);
        }
    }

    public function index(Index $index, SplFileInfo $info, Node $node): void
    {
        assert($node instanceof QualifiedName);

        // this is slow
        $name = $node->getResolvedName() ? $node->getResolvedName() : null;

        if (null === $name) {
            $name = (string)$node;
        }

        $targetRecord = $index->get(FunctionRecord::fromName($name));
        assert($targetRecord instanceof FunctionRecord);
        $targetRecord->setLastModified($info->getCTime());
        $targetRecord->setStart(ByteOffset::fromInt($node->getStart()));
        $targetRecord->setFilePath($info->getPathname());
        $targetRecord->addReference($info->getPathname());
        $index->write($targetRecord);

        $fileRecord = $index->get(FileRecord::fromFileInfo($info));
        assert($fileRecord instanceof FileRecord);
        $fileRecord->addReference(new RecordReference(FunctionRecord::RECORD_TYPE, $targetRecord->identifier(), $node->getStart()));
        $index->write($fileRecord);
    }
}
