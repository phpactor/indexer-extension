<?php

namespace Phpactor\ProjectQuery\Adapter\Worse;

use DTL\Invoke\Invoke;
use DateTimeImmutable;
use Generator;
use Phpactor\Filesystem\Domain\FileList;
use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\Filesystem\Domain\Filesystem;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\ProjectQuery\Model\Index;
use Phpactor\ProjectQuery\Model\IndexBuilder;
use Phpactor\ProjectQuery\Model\Record\ClassRecord;
use Phpactor\WorseReflection\Core\Exception\SourceNotFound;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionClassCollection;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionCollection;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionInterface;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use Psr\Log\LoggerInterface;
use RuntimeException;

class WorseIndexBuilder implements IndexBuilder
{
    /**
     * @var Index
     */
    private $index;

    /**
     * @var Filesystem
     */
    private $filesystem;

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
        Filesystem $filesystem,
        SourceCodeReflector $reflector,
        LoggerInterface $logger
    ) {
        $this->index = $index;
        $this->filesystem = $filesystem;
        $this->reflector = $reflector;
        $this->logger = $logger;
    }

    /**
     * @return Generator<string>
     */
    public function build(?string $subPath = null): Generator
    {
        $this->logger->info(sprintf(
            'Last update: %s (%s)',
            $this->index->lastUpdate(),
            $this->formatTimestamp()
        ));
        $this->logger->info(sprintf('Starting pass 1/2: Indexing classes'));

        foreach ($this->createFileIterator($subPath) as $fileInfo) {
            if ($this->index->isFresh($fileInfo)) {
                continue;
            }

            assert($fileInfo instanceof FilePath);
            $this->logger->debug(sprintf('Indexing: %s', $fileInfo->path()));
            try {
                $this->indexClasses(
                    $fileInfo,
                    $this->reflector->reflectClassesIn(file_get_contents($fileInfo->path()))
                );
            } catch (SourceNotFound $e) {
                $this->logger->error($e->getMessage());
            }

            yield $fileInfo->path();
        }

        $this->logger->info(sprintf('Starting pass 2/2: Indexing implementations'));
        foreach ($this->createFileIterator($subPath) as $fileInfo) {
            if ($this->index->isFresh($fileInfo)) {
                continue;
            }
            $this->logger->debug(sprintf('Implementations: %s', $fileInfo->path()));

            assert($fileInfo instanceof FilePath);
            try {
                $this->updateClassRelations(
                    $fileInfo,
                    $this->reflector->reflectClassesIn(file_get_contents($fileInfo->path()))
                );
            } catch (SourceNotFound $e) {
                $this->logger->error($e->getMessage());
            }

            yield $fileInfo->path();
        }
        $this->index->write()->timestamp();
    }

    /**
     * @param ReflectionClassCollection<ReflectionClassLike> $classes
     */
    private function indexClasses(FilePath $fileInfo, ReflectionClassCollection $classes): void
    {
        foreach ($classes as $reflectionClass) {
            assert($reflectionClass instanceof ReflectionClassLike);

            $name = $reflectionClass->name()->full();
            if (empty($name)) {
                continue;
            }
            $record = Invoke::new(ClassRecord::class, [
                'lastModified' => $fileInfo->asSplFileInfo()->getMTime(),
                'fqn' => FullyQualifiedName::fromString($name),
                'type' => WorseUtil::classType($reflectionClass)
            ]);

            $this->index->write()->class($record);
        }
    }

    /**
     * @param ReflectionClassCollection<ReflectionClassLike> $classes
     */
    private function updateClassRelations(FilePath $fileInfo, ReflectionClassCollection $classes): void
    {
        foreach ($classes as $classLike) {
            if ($classLike instanceof ReflectionInterface) {
                $this->updateClassImplementations($classLike, iterator_to_array($classLike->parents()));
            }

            if ($classLike instanceof ReflectionClass) {
                $this->updateClassImplementations($classLike, iterator_to_array($classLike->interfaces()));
                $this->updateClassImplementations($classLike, $this->descendents($classLike));
            }
        }
    }

    /**
     * @param ReflectionCollection<ReflectionClassLike> $implementedClasses
     * @param array<ReflectionClassLike> $implementedClasses
     */
    private function updateClassImplementations(
        ReflectionClassLike $implementingClass,
        array $implementedClasses
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

    /**
     * @return FileList<FilePath>
     */
    private function createFileIterator(?string $subPath = null): FileList
    {
        $files = $this->filesystem->fileList()->phpFiles();
        if ($subPath) {
            $files = $files->within(FilePath::fromString($subPath));
        }
        return $files;
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
}
