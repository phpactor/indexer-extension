<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\Node;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use SplFileInfo;
use Phpactor\Indexer\Model\Index;

class ClassDeclarationIndexer extends ClassLikeIndexer
{
    public function canIndex(Node $node): bool
    {
        return $node instanceof ClassDeclaration;
    }

    public function index(Index $index, SplFileInfo $info, Node $node): void
    {
        assert($node instanceof ClassDeclaration);
        $record = $this->getClassLikeRecord('class', $node, $index, $info);

        $this->removeImplementations($index, $record);
        $record->clearImplemented();

        $this->indexClassInterfaces($index, $record, $node);
        $this->indexBaseClass($index, $record, $node);

        $index->write($record);
    }

    private function indexClassInterfaces(Index $index, ClassRecord $classRecord, ClassDeclaration $node): void
    {
        // @phpstan-ignore-next-line because ClassInterfaceClause _can_ (and has been) be NULL
        if (null === $interfaceClause = $node->classInterfaceClause) {
            return;
        }

        if (null == $interfaceList = $interfaceClause->interfaceNameList) {
            return;
        }

        $this->indexInterfaceList($interfaceList, $classRecord, $index);
    }

    private function indexBaseClass(Index $index, ClassRecord $record, ClassDeclaration $node): void
    {
        // @phpstan-ignore-next-line because classBaseClause _can_ be NULL
        if (null === $baseClause = $node->classBaseClause) {
            return;
        }

        // @phpstan-ignore-next-line because classBaseClause _can_ be NULL
        if (null === $baseClass = $baseClause->baseClass) {
            return;
        }

        $name = $baseClass->getNamespacedName()->getFullyQualifiedNameText();
        $record->addImplements(FullyQualifiedName::fromString($name));
        $baseClassRecord = $index->get(ClassRecord::fromName($name));
        assert($baseClassRecord instanceof ClassRecord);
        $baseClassRecord->addImplementation($record->fqn());
        $index->write($baseClassRecord);
    }
}
