<?php

namespace Phpactor\ProjectQuery\Adapter\Worse;

use DTL\Invoke\Invoke;
use Phpactor\Filesystem\Domain\Filesystem;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\ProjectQuery\Model\Index;
use Phpactor\ProjectQuery\Model\IndexBuilder;
use Phpactor\ProjectQuery\Model\Record\ClassRecord;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionClassCollection;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionCollection;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionInterface;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use SplFileInfo;

class WorseIndexBuilder implements IndexBuilder
{
    /**
     * @var Index
     */
    private $index;

    /**
     * @var string
     */
    private $projectPath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var SourceCodeReflector
     */
    private $reflector;

    public function __construct(
        Index $index,
        Filesystem $filesystem,
        SourceCodeReflector $reflector,
        string $projectPath
    ) {
        $this->index = $index;
        $this->projectPath = $projectPath;
        $this->filesystem = $filesystem;
        $this->reflector = $reflector;
    }

    public function refresh(): void
    {
        // Pass 1
        foreach ($this->filesystem->fileList()->phpFiles() as $fileInfo) {
            assert($fileInfo instanceof SplFileInfo);
            $this->indexClasses(
                $fileInfo,
                $this->reflector->reflectClassesIn(file_get_contents($fileInfo->getPathname()))
            );
        }

        // Pass 2
        foreach ($this->filesystem->fileList()->phpFiles() as $fileInfo) {
            assert($fileInfo instanceof SplFileInfo);
            $this->updateClassRelations(
                $fileInfo,
                $this->reflector->reflectClassesIn(file_get_contents($fileInfo->getPathname()))
            );
        }
    }

    /**
     * @param ReflectionClassCollection<ReflectionClassLike> $classes
     */
    private function indexClasses(SplFileInfo $fileInfo, ReflectionClassCollection $classes): void
    {
        foreach ($classes as $reflectionClass) {
            assert($reflectionClass instanceof ReflectionClassLike);

            $record = Invoke::new(ClassRecord::class, [
                'lastModified' => $fileInfo->getMTime(),
                'fqn' => $reflectionClass->name()->full(),
                'type' => WorseUtil::classType($reflectionClass)
            ]);

            $this->index->write()->class($record);
        }
    }

    /**
     * @param ReflectionClassCollection<ReflectionClassLike> $classes
     */
    private function updateClassRelations(SplFileInfo $fileInfo, ReflectionClassCollection $classes): void
    {
        foreach ($classes as $classLike) {
            if ($classLike instanceof ReflectionInterface) {
                $this->updateClassImplementations($classLike, $classLike->parents());
            }
        }
    }

    /**
     * @param ReflectionCollection<ReflectionClassLike> $implementedClasses
     */
    private function updateClassImplementations(
        ReflectionClassLike $implementingClass,
        ReflectionCollection $implementedClasses
    ): void {
        foreach ($implementedClasses as $implementedClass) {
            $record = $this->index->query()->class(
                FullyQualifiedName::fromString(
                    $implementedClass->name()->full()
                )
            );

            if (null === $record) {
                return;
            }

            $record->addImplementation($implementingClass);
            $this->index->write()->class($record);
        }
    }
}
