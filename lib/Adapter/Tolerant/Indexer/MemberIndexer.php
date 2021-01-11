<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Node\QualifiedName;
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

    public function beforeParse(Index $index, SplFileInfo $info): void
    {
        $fileRecord = $index->get(FileRecord::fromFileInfo($info));
        assert($fileRecord instanceof FileRecord);

        foreach ($fileRecord->references() as $outgoingReference) {
            if ($outgoingReference->type() !== MemberRecord::RECORD_TYPE) {
                continue;
            }

            $memberRecord = $index->get(MemberRecord::fromIdentifier($outgoingReference->identifier()));
            assert($memberRecord instanceof MemberRecord);
            $memberRecord->removeReference($fileRecord->identifier());
            $index->write($memberRecord);
            $fileRecord->removeReferencesToRecordType($outgoingReference->type());
            $index->write($fileRecord);
        }
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
        $containerType = $node->scopeResolutionQualifier;

        if (!$containerType instanceof QualifiedName) {
            return;
        }

        $containerType = $this->resolveContainerType($containerType, $node);
        $memberName = $this->resolveScopedPropertyAccessName($node);

        if (empty($memberName)) {
            return;
        }

        $memberType = $this->resolveScopedPropertyAccessMemberType($node);

        $this->writeIndex($index, $memberType, $containerType, $memberName, $info, $this->resolveStart($node->memberName));
    }

    private function resolveScopedPropertyAccessMemberType(ScopedPropertyAccessExpression $node): string
    {
        if ($node->parent instanceof CallExpression) {
            return MemberRecord::TYPE_METHOD;
        }

        if ($node->memberName instanceof Variable) {
            return MemberRecord::TYPE_PROPERTY;
        }

        return MemberRecord::TYPE_CONSTANT;
    }

    private function resolveMemberAccessType(MemberAccessExpression $node): string
    {
        if ($node->parent instanceof CallExpression) {
            return MemberRecord::TYPE_METHOD;
        }

        return MemberRecord::TYPE_PROPERTY;
    }

    private function resolveScopedPropertyAccessName(ScopedPropertyAccessExpression $node): string
    {
        $memberName = $node->memberName;

        if ($memberName instanceof Token) {
            return (string)$memberName->getText($node->getFileContents());
        }

        if (!$memberName instanceof Variable) {
            return '';
        }

        return (string)$memberName->getName();
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

        $this->writeIndex($index, $memberType, null, (string)$memberName, $info, $this->resolveStart($node->memberName));
    }

    private function writeIndex(Index $index, string $memberType, ?string $containerFqn, string $memberName, SplFileInfo $info, int $offsetStart): void
    {
        $record = $index->get(MemberRecord::fromMemberReference(MemberReference::create($memberType, $containerFqn, $memberName)));
        assert($record instanceof MemberRecord);
        $record->addReference($info->getPathname());
        $index->write($record);
        
        $fileRecord = $index->get(FileRecord::fromFileInfo($info));
        assert($fileRecord instanceof FileRecord);
        $fileRecord->addReference(RecordReference::fromRecordAndOffsetAndContainerType($record, $offsetStart, $containerFqn));
        $index->write($fileRecord);
    }

    /**
     * @param Token|Node $nodeOrToken
     */
    private function resolveStart($nodeOrToken): int
    {
        if ($nodeOrToken instanceof Token) {
            return $nodeOrToken->start;
        }

        return $nodeOrToken->getStart();
    }

    private function resolveContainerType(QualifiedName $containerType, Node $node): ?string
    {
        $containerType = (string)$containerType->getResolvedName();

        // let static analysis solve these later - we cannot determine the
        // correct values efficiently now (traits etc).
        if (in_array($containerType, ['self', 'static', 'parent'])) {
            return null;
        }

        return $containerType;
    }
}
