<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\ConstElement;
use Microsoft\PhpParser\Node\DelimitedList\ConstElementList;
use Microsoft\PhpParser\Node\Expression\ArgumentExpression;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\Statement\ConstDeclaration;
use Microsoft\PhpParser\Node\StringLiteral;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Indexer\Model\Record\ConstantRecord;
use Phpactor\TextDocument\ByteOffset;
use SplFileInfo;
use Phpactor\Indexer\Model\Index;

class ConstantDeclarationIndexer implements TolerantIndexer
{
    public function canIndex(Node $node): bool
    {
        if ($node instanceof ConstDeclaration) {
            return true;
        }

        if (!$node instanceof CallExpression) {
            return false;
        }

        /** @phpstan-ignore-next-line */
        if (!$node->callableExpression instanceof QualifiedName) {
            return false;
        }

        /** @phpstan-ignore-next-line */
        if ('define' === $node->callableExpression->getNamespacedName()->__toString()) {
            return true;
        }

        return false;
    }

    public function index(Index $index, SplFileInfo $info, Node $node): void
    {
        if ($node instanceof ConstDeclaration) {
            $this->fromConstDeclaration($node, $index, $info);
            return;
        }

        if ($node instanceof CallExpression) {
            $this->fromDefine($node, $index, $info);
            return;
        }
    }

    public function beforeParse(Index $index, SplFileInfo $info): void
    {
    }

    private function fromConstDeclaration(Node $node, Index $index, SplFileInfo $info): void
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

    private function fromDefine(CallExpression $node, Index $index, SplFileInfo $info): void
    {
        assert($node instanceof CallExpression);

        if (null === $node->argumentExpressionList) {
            return;
        }

        foreach ($node->argumentExpressionList->getChildNodes() as $expression) {
            if (!$expression instanceof ArgumentExpression) {
                return;
            }
            $string = $expression->expression;
            if (!$string instanceof StringLiteral) {
                return;
            }

            $record = $index->get(ConstantRecord::fromName($string->getStringContentsText()));
            assert($record instanceof ConstantRecord);
            $record->setStart(ByteOffset::fromInt($node->getStart()));
            $record->setFilePath($info->getPathname());
            $index->write($record);
        }
    }
}
