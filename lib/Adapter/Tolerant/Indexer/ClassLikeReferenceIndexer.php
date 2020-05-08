<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\ClassLike;
use Microsoft\PhpParser\NamespacedNameInterface;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\Statement\TraitDeclaration;
use Microsoft\PhpParser\Node\Statement\InterfaceDeclaration;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\TextDocument\ByteOffset;
use RuntimeException;
use SplFileInfo;

class ClassLikeReferenceIndexer extends AbstractClassLikeIndexer
{
    public function canIndex(Node $node): bool
    {
        // its hard to tell what is a class and what is not but all function
        // calls have parent that is instanceof CallExpression
        return 
            (
                $node instanceof QualifiedName ||
                $node instanceof ClassLike
            )
            && !$node->parent instanceof CallExpression;
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
        $name = $this->resolveName($node);

        if (null === $name) {
            return;
        }

        if (in_array((string)$name, ['static', 'parent', 'self'])) {
            return;
        }

        $targetRecord = $index->get(ClassRecord::fromName($name));
        assert($targetRecord instanceof ClassRecord);
        $targetRecord->setLastModified($info->getCTime());
        $targetRecord->setStart(ByteOffset::fromInt($node->getStart()));
        $targetRecord->setFilePath($info->getPathname());
        $targetRecord->addReference($info->getPathname());

        $index->write($targetRecord);

        $fileRecord = $index->get(FileRecord::fromFileInfo($info));
        assert($fileRecord instanceof FileRecord);
        $fileRecord->addReference(new RecordReference(ClassRecord::RECORD_TYPE, $targetRecord->identifier(), $node->getStart()));
        $index->write($fileRecord);
    }

    private function resolveName(Node $node): ?string
    {
        if ($node instanceof QualifiedName) {
            return $node->getResolvedName() ? $node->getResolvedName() : null;
        }

        if ($node instanceof NamespacedNameInterface) {
            return $node->getNamespacedName();
        }

        throw new RuntimeException(sprintf(
            'Do not know how to get name for class "%s"',
            get_class($node)
        ));
    }
}
