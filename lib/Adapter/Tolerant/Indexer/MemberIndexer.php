<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\Statement\ExpressionStatement;
use Microsoft\PhpParser\Token;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\MemberReference;
use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Model\Record\MemberRecord;
use SplFileInfo;

class MemberIndexer implements TolerantIndexer
{
    public function canIndex(Node $node): bool
    {
        return $node instanceof ScopedPropertyAccessExpression || $node instanceof MemberAccessExpression;
    }

    public function index(Index $index, SplFileInfo $info, Node $node): void
    {
        if ($node instanceof ScopedPropertyAccessExpression) {
            $this->indexScopedPropertyAccess($index, $info, $node);
            return;
        }

        if ($node instanceof MemberAccessExpression) {
            $this->indexMemberAccessExpression($index, $info, $node);
            return;
        }
    }

    private function indexScopedPropertyAccess(Index $index, SplFileInfo $info, ScopedPropertyAccessExpression $node): void
    {
        $containerFqn = $node->scopeResolutionQualifier;

        if (!$containerFqn instanceof QualifiedName) {
            return;
        }

        $containerFqn = (string)$containerFqn->getResolvedName();
        $memberName = $this->resolveScopedPropertyAccessName($node);

        if (empty($memberName)) {
            return;
        }

        $memberType = $this->resolveScopedPropertyAccessMemberType($node);

        $this->writeIndex($index, $memberType, $containerFqn, $memberName, $info, $node);
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

    private function resolveScopedPropertyAccessMemberType(ScopedPropertyAccessExpression $node): string
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

        return 'unknown';
    }

    private function resolveMemberAccessType(MemberAccessExpression $node): string
    {
        if ($node->parent instanceof CallExpression) {
            return 'method';
        }

        return 'property';
    }

    private function resolveScopedPropertyAccessName(ScopedPropertyAccessExpression $node): string
    {
        $memberName = $node->memberName;

        if ($memberName instanceof Token) {
            return (string)$memberName->getText($node->getFileContents());
        }

        return $memberName->getText();
    }

    private function indexMemberAccessExpression(Index $index, SplFileInfo $info, MemberAccessExpression $node): void
    {
        $memberName = $node->memberName;

        /** @phpstan-ignore-next-line Member name could be NULL */
        if (null === $memberName) {
            return;
        }

        $memberName = $memberName->getText($node->getFileContents());

        if (empty($memberName)) {
            return;
        }

        $memberType = $this->resolveMemberAccessType($node);

        $this->writeIndex($index, $memberType, null, (string)$memberName, $info, $node);
    }

    private function writeIndex(Index $index, string $memberType, ?string $containerFqn, string $memberName, SplFileInfo $info, Node $node): void
    {
        $record = $index->get(MemberRecord::fromMemberReference(MemberReference::create($memberType, $containerFqn, $memberName)));
        assert($record instanceof MemberRecord);
        $record->setLastModified($info->getCTime());
        $record->setFilePath($info->getPathname());
        $record->addReference($info->getPathname());
        $index->write($record);
        
        $fileRecord = $index->get(FileRecord::fromFileInfo($info));
        assert($fileRecord instanceof FileRecord);
        $fileRecord->addReference(RecordReference::fromRecordAndOffsetAndContainerType($record, $node->getStart(), $containerFqn));
        $index->write($fileRecord);
    }
}
