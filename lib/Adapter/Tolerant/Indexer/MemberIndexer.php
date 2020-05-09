<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\Statement\ExpressionStatement;
use Microsoft\PhpParser\Token;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Util\ReferenceRemover;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\MemberReference;
use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Model\Record\MemberRecord;
use SplFileInfo;

class MemberIndexer implements TolerantIndexer
{
    public function canIndex(Node $node): bool
    {
        return $node instanceof ScopedPropertyAccessExpression;
    }

    public function index(Index $index, SplFileInfo $info, Node $node): void
    {
        assert($node instanceof ScopedPropertyAccessExpression);

        $containerFqn = $node->scopeResolutionQualifier;
        if (!$containerFqn instanceof QualifiedName) {
            return;
        }

        $containerFqn = (string)$containerFqn->getResolvedName();

        $memberName = $this->resolveName($node);

        if (empty($memberName)) {
            return;
        }

        $record = $index->get(MemberRecord::fromMemberReference(MemberReference::create($this->resolveMemberType($node), $containerFqn, $memberName)));
        assert($record instanceof MemberRecord);
        $record->setLastModified($info->getCTime());
        $record->setFilePath($info->getPathname());
        $record->addReference($info->getPathname());
        $index->write($record);

        $fileRecord = $index->get(FileRecord::fromFileInfo($info));
        assert($fileRecord instanceof FileRecord);
        $fileRecord->addReference(new RecordReference(MemberRecord::RECORD_TYPE, $record->identifier(), $node->getStart()));
        $index->write($fileRecord);
    }

    public function beforeParse(Index $index, SplFileInfo $info): void
    {
        $fileRecord = $index->get(FileRecord::fromFileInfo($info));
        assert($fileRecord instanceof FileRecord);

        foreach ($fileRecord->references() as $outgoingReference) {
            if ($outgoingReference->type() !== MemberRecord::RECORD_TYPE) {
                continue;
            }

            $record = $index->get(MemberRecord::fromIdentifier($outgoingReference->identifier()));
            assert($record instanceof MemberRecord);
            $record->removeReference($fileRecord->identifier());
            $index->write($record);
        }
    }

    private function resolveMemberType(ScopedPropertyAccessExpression $node): string
    {
        if ($node->parent instanceof CallExpression) {
            return 'method';
        }

        if ($node->memberName instanceof Variable) {
            return 'property';
        }

        if ($node->parent instanceof ExpressionStatement) {
            return 'constant';
        }

        return 'property';
    }

    private function resolveName(ScopedPropertyAccessExpression $node): string
    {
        $memberName = $node->memberName;

        if ($memberName instanceof Token) {
            return (string)$memberName->getText($node->getFileContents());
        }

        return $memberName->getText();
    }
}
