<?php

namespace Phpactor\WorkspaceQuery\Adapter\Worse;

use DTL\Invoke\Invoke;
use Generator;
use Phpactor\Filesystem\Domain\FileList;
use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\Filesystem\Domain\Filesystem;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\WorkspaceQuery\Model\Index;
use Phpactor\WorkspaceQuery\Model\IndexBuilder;
use Phpactor\WorkspaceQuery\Model\Record\ClassRecord;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\WorseReflection\Core\Exception\SourceNotFound;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionClassCollection;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionCollection;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionInterface;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use Phpactor\WorseReflection\Core\SourceCode;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SplFileInfo;
use function Safe\file_get_contents;

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

    public function build(?string $subPath = null): void
    {
        iterator_to_array($this->buildGenerator($subPath));
    }

    /**
     * @return Generator<string>
     */
    public function buildGenerator(?string $subPath = null): Generator
    {
        $this->logger->info(sprintf(
            'Last update: %s (%s)',
            $this->index->lastUpdate(),
            $this->formatTimestamp()
        ));

        yield from $this->indexer($subPath);

        $this->index->write()->timestamp();
    }

    /**
     * @return Generator<SplFileInfo>
     */
    private function indexer(?string $subPath): Generator
    {
        $count = 0;
        foreach ($this->createFileIterator($subPath) as $fileInfo) {
            if ($this->index->isFresh($fileInfo)) {
                continue;
            }

            assert($fileInfo instanceof FilePath);

            $this->logger->debug(sprintf('Indexing: %s', $fileInfo->path()));

            try {
                $this->indexClasses(
                    $fileInfo,
                    $this->reflector->reflectClassesIn(
                        SourceCode::fromPathAndString(
                            $fileInfo->path(),
                            file_get_contents($fileInfo->path())
                        )
                    )
                );
            } catch (SourceNotFound $e) {
                $this->logger->error($e->getMessage());
            }

            yield $fileInfo->path();

            $count++;
        }

        return $count > 0;
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
     * @param ReflectionClassCollection<ReflectionClassLike> $classes
     */
    private function indexClasses(FilePath $fileInfo, ReflectionClassCollection $classes): void
    {
        $mtime = $fileInfo->asSplFileInfo()->getMTime();
        foreach ($classes as $reflectionClass) {
            $this->createClassIndex($reflectionClass, $mtime);
            $this->updateClassRelations(
                $reflectionClass
            );
        }
    }

    private function createClassIndex(ReflectionClassLike $reflectionClass, int $mtime): ?ClassRecord
    {
        assert($reflectionClass instanceof ReflectionClassLike);
        
        $name = $reflectionClass->name()->full();
        
        if (empty($name)) {
            return null;
        }

        $name = FullyQualifiedName::fromString($name);

        if ($class = $this->index->query()->class($name)) {
            return $class;
        }
        
        $record = Invoke::new(ClassRecord::class, [
            'lastModified' => $mtime,
            'fqn' => $name,
            'type' => WorseUtil::classType($reflectionClass),
            'filePath' => $reflectionClass->sourceCode()->path(),
            'start' => ByteOffset::fromInt($reflectionClass->position()->start()),
        ]);

        $this->index->write()->class($record);

        return $record;
    }

    private function updateClassRelations(ReflectionClassLike $classLike): void
    {
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
        ReflectionClassLike $implementingClass,
        array $implementedClasses
    ): void {
        foreach ($implementedClasses as $implementedClass) {
            $implementedFqn = FullyQualifiedName::fromString(
                $implementedClass->name()->full()
            );

            $mtime = filemtime($implementedClass->sourceCode()->path());

            $record = $this->createClassIndex($implementedClass, $mtime ?: 0);

            if (null === $record) {
                continue;
            }

            $record->addImplementation($implementingClass);
            $this->index->write()->class($record);
        }
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

    public function size(): int
    {
        return count(iterator_to_array($this->createFileIterator()));
    }
}
