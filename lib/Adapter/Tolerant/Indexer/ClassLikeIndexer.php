<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\NamespacedNameInterface;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\DelimitedList\QualifiedNameList;
use Microsoft\PhpParser\Node\Statement\InterfaceDeclaration;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\Indexer\Model\Index;
use SplFileInfo;

abstract class ClassLikeIndexer implements TolerantIndexer
{
    protected function removeImplementations(Index $index, ClassRecord $record): void
    {
        foreach ($record->implements() as $implementedClass) {
            $implementedRecord = $index->get(ClassRecord::fromName($implementedClass));
        
            if (false === $implementedRecord->removeImplementation($record->fqn())) {
                continue;
            }
        
            $index->write($implementedRecord);
        }
    }

    protected function indexInterfaceList(QualifiedNameList $interfaceList, ClassRecord $classRecord, Index $index): void
    {
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

    protected function getClassLikeRecord(string $type, Node $node, Index $index, SplFileInfo $info): ClassRecord
    {
        assert($node instanceof NamespacedNameInterface);
        $record = $index->get(ClassRecord::fromName($node->getNamespacedName()->getFullyQualifiedNameText()));
        assert($record instanceof ClassRecord);
        $record->setLastModified($info->getCTime());
        $record->setStart(ByteOffset::fromInt($node->getStart()));
        $record->setFilePath($info->getPathname());
        $record->setType($type);
        return $record;
    }
}
