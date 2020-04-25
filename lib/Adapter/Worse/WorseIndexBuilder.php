<?php

namespace Phpactor\Indexer\Adapter\Worse;

use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Model\Record;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\WorseReflection\Core\Exception\SourceNotFound;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionClassCollection;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionCollection;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionFunctionCollection;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionFunction;
use Phpactor\WorseReflection\Core\Reflection\ReflectionInterface;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use Phpactor\WorseReflection\Core\SourceCode;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Safe\Exceptions\FilesystemException;
use SplFileInfo;
use function Safe\file_get_contents;

class WorseIndexBuilder implements IndexBuilder
{
    /**
     * @var Index
     */
    private $index;

    /**
     * @var SourceCodeReflector
     */
    private $reflector;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Index $index,
        SourceCodeReflector $reflector,
        LoggerInterface $logger
    ) {
        $this->index = $index;
        $this->reflector = $reflector;
        $this->logger = $logger;
    }

    public function index(SplFileInfo $fileInfo): void
    {
        $this->logger->debug(sprintf('Indexing: %s', $fileInfo->getPathname()));

        try {
            try {
                $contents = file_get_contents($fileInfo->getPathname());
            } catch (FilesystemException $filesystemException) {
                $this->logger->warning(sprintf(
                    'Error indexing file "%s": %s',
                    $fileInfo->getPathname(),
                    $filesystemException->getMessage()
                ));
                return;
            }
            $this->indexClasses(
                $fileInfo,
                $this->reflector->reflectClassesIn(
                    SourceCode::fromPathAndString(
                        $fileInfo->getPathname(),
                        $contents
                    )
                )
            );
        } catch (SourceNotFound $e) {
            $this->logger->error($e->getMessage());
        }

        try {
            $this->indexFunctions(
                $fileInfo,
                $this->reflector->reflectFunctionsIn(
                    SourceCode::fromPathAndString(
                        $fileInfo->getPathname(),
                        file_get_contents($fileInfo->getPathname())
                    )
                )
            );
        } catch (SourceNotFound $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @param ReflectionClassCollection<ReflectionClassLike> $classes
     */
    private function indexClasses(SplFileInfo $fileInfo, ReflectionClassCollection $classes): void
    {
        $mtime = $fileInfo->getCTime();
        foreach ($classes as $reflectionClass) {
            $record = $this->createOrGetClassRecord($reflectionClass->name()->full());
            $record->withLastModified($mtime);
            $record->withType(WorseUtil::classType($reflectionClass));
            $record->withStart(ByteOffset::fromInt($reflectionClass->position()->start()));
            $record->withFilePath($fileInfo->getPathname());
            $this->index->write($record);

            $this->updateClassRelations(
                $reflectionClass
            );
        }
    }

    private function createOrGetClassRecord(string $name): ?ClassRecord
    {
        if (empty($name)) {
            return null;
        }

        return $this->index->get(ClassRecord::fromName($name));
    }

    private function updateClassRelations(ReflectionClassLike $classLike): void
    {
        $classRecord = $this->index->query()->class(FullyQualifiedName::fromString($classLike->name()));
        $this->removeExistingReferences($classRecord);

        if ($classLike instanceof ReflectionInterface) {
            $this->updateClassImplementations($classLike, iterator_to_array($classLike->parents()));
        }

        if ($classLike instanceof ReflectionClass) {
            $this->updateClassImplementations($classLike, iterator_to_array($classLike->interfaces()));
            $this->updateClassImplementations($classLike, $this->descendents($classLike));
        }
    }

    /**
     * @param ReflectionCollection<ReflectionClassLike> $implementedClasses
     * @param array<ReflectionClassLike> $implementedClasses
     */
    private function updateClassImplementations(
        ReflectionClassLike $classReflection,
        array $implementedClasses
    ): void {
        $classRecord = $this->createOrGetClassRecord($classReflection->name()->full());

        foreach ($implementedClasses as $implementedClass) {
            $implementedFqn = FullyQualifiedName::fromString(
                $implementedClass->name()->full()
            );

            $implementedRecord = $this->createOrGetClassRecord($implementedClass->name()->full());

            $classRecord->addImplements(FullyQualifiedName::fromString($implementedClass->name()));
            $implementedRecord->addImplementation(FullyQualifiedName::fromString($classReflection->name()));

            $this->index->write($implementedRecord);
        }

        $this->index->write($classRecord);
    }

    /**
     * @return array<ReflectionClass>
     */
    private function descendents(ReflectionClass $classLike): array
    {
        $parents = [];
        while ($parent = $classLike->parent()) {
            // avoid self-referencing classes
            if (array_key_exists($parent->name()->full(), $parents)) {
                break;
            }
            $parents[$parent->name()->full()] = $parent;
            $classLike = $parent;
        }
        return array_values($parents);
    }

    private function formatTimestamp(): string
    {
        $format = date('c', $this->index->lastUpdate());
        if (!$format) {
            throw new RuntimeException('This never happens');
        }
        return $format;
    }

    private function removeExistingReferences(ClassRecord $classRecord): void
    {
        foreach ($classRecord->implementedClasses() as $implementedClass) {
            $implementedRecord = $this->index->query()->class(
                FullyQualifiedName::fromString($implementedClass)
            );
            if (null === $implementedRecord) {
                continue;
            }
            $implementedRecord->removeClass($classRecord->fqn());
            $this->index->write($implementedRecord);
        }
    }

    /**
     * @param ReflectionFunctionCollection<ReflectionFunction> $reflectionFunctionCollection
     */
    private function indexFunctions(SplFileInfo $fileInfo, ReflectionFunctionCollection $reflectionFunctionCollection): void
    {
        $mtime = $fileInfo->getCTime();
        foreach ($reflectionFunctionCollection as $reflectionFunction) {
            $function = $this->createFunctionRecord($reflectionFunction, $mtime);
            $function->withLastModified($mtime);
            $function->withFilePath($reflectionFunction->sourceCode()->path());
            $function->withStart(ByteOffset::fromInt($reflectionFunction->position()->start()));
            $this->index->write($function);
        }
    }

    private function createFunctionRecord(ReflectionFunction $reflectionFunction, int $mtime): ?FunctionRecord
    {
        $name = $reflectionFunction->name()->full();

        if (empty($name)) {
            return null;
        }

        $name = FullyQualifiedName::fromString($name);

        return $this->index->get(FunctionRecord::fromName($name));
    }

    public function done(): void
    {
        $this->index->updateTimestamp();
    }
}
