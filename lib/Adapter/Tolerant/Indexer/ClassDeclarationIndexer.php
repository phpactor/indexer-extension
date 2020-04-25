<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\Node;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Name\FullyQualifiedName;
use Microsoft\PhpParser\Node\QualifiedName;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use SplFileInfo;
use Phpactor\Indexer\Model\Index;

class ClassDeclarationIndexer implements TolerantIndexer
{
    public function canIndex(Node $node): bool
    {
        return $node instanceof ClassDeclaration;
    }

    public function index(Index $index, SplFileInfo $info, Node $node): void
    {
        assert($node instanceof ClassDeclaration);

        $record = $index->get(ClassRecord::fromName($node->getNamespacedName()->getFullyQualifiedNameText()));
        assert($record instanceof ClassRecord);
        $record->setLastModified($info->getCTime());
        $record->setStart(ByteOffset::fromInt($node->getStart()));
        $record->setFilePath($info->getPathname());
        $record->setType('class');

        // remove any references to this class and other classes before
        // updating with the current data
        $this->removeImplementations($index, $record);
        $record->clearImplemented();

        $this->indexClassInterfaces($index, $record, $node);
        $this->indexBaseClass($index, $record, $node);

        $index->write($record);
    }

    private function removeImplementations(Index $index, ClassRecord $record): void
    {
        foreach ($record->implements() as $implementedClass) {
            $implementedRecord = $index->get(ClassRecord::fromName($implementedClass));
        
            if (false === $implementedRecord->removeImplementation($record->fqn())) {
                continue;
            }
        
            $index->write($implementedRecord);
        }
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

        foreach ($interfaceList->children as $interfaceName) {
            if (!$interfaceName instanceof QualifiedName) {
                continue;
            }

            $classRecord->addImplements(FullyQualifiedName::fromString($interfaceName->getNamespacedName()->getFullyQualifiedNameText()));

            $interfaceRecord = $index->get(ClassRecord::fromName($interfaceName));
            assert($interfaceRecord instanceof ClassRecord);
            $interfaceRecord->addImplementation($classRecord->fqn());

            $index->write($interfaceRecord);
        }
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
