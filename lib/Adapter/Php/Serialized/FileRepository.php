<?php

namespace Phpactor\Indexer\Adapter\Php\Serialized;

use Phpactor\Indexer\Model\Exception\CorruptedRecord;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\Record;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\mkdir;

class FileRepository
{
    private const FUNC_PREFIX = '__func__';
    private const CLASS_PREFIX = '__class__';

    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $lastUpdate;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(string $path, ?LoggerInterface $logger = null)
    {
        $this->path = $path;
        $this->initializeLastUpdate();
        $this->logger = $logger ?: new NullLogger();
    }

    public function putClass(ClassRecord $class): void
    {
        $this->serializeRecord(self::CLASS_PREFIX, $class);
    }

    public function getClass(FullyQualifiedName $name): ?ClassRecord
    {
        return $this->deserializeRecord(self::CLASS_PREFIX, $name, ClassRecord::class);
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (file_exists($path)) {
            return;
        }

        mkdir($path, 0777, true);
    }

    public function putTimestamp(int $time = null): void
    {
        $time = $time ?? time();
        $this->ensureDirectoryExists(dirname($this->timestampPath()));
        file_put_contents($this->timestampPath(), $time);
        $this->lastUpdate = $time;
    }

    public function lastUpdate(): int
    {
        return $this->lastUpdate;
    }

    private function initializeLastUpdate(): void
    {
        $this->lastUpdate = file_exists($this->timestampPath()) ?
            (int)file_get_contents($this->timestampPath()) :
            0
        ;
    }

    private function timestampPath(): string
    {
        return $this->path . '/timestamp';
    }

    public function reset(): void
    {
        $this->putTimestamp(0);
    }

    public function putFunction(FunctionRecord $function): void
    {
        $this->serializeRecord(self::FUNC_PREFIX, $function);
    }

    public function getFunction(FullyQualifiedName $name): ?FunctionRecord
    {
        $path = $this->pathFor(self::FUNC_PREFIX, $name);

        if (!file_exists($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        $deserialized = unserialize($contents);

        // handle invalid entries (e.g. old data structures)
        if (!$deserialized instanceof FunctionRecord) {
            return null;
        }

        return $deserialized;
    }

    private function serializeRecord(string $prefix, Record $record): void
    {
        $path = $this->pathFor($prefix, $record->fqn());
        $this->ensureDirectoryExists(dirname($path));
        file_put_contents($path, serialize($record));
    }

    private function pathFor(string $namespace, FullyQualifiedName $class): string
    {
        $hash = md5($class->__toString());
        return sprintf(
            '%s/%s_%s/%s/%s.cache',
            $this->path,
            $namespace,
            substr($hash, 0, 1),
            substr($hash, 1, 1),
            $hash
        );
    }

    /**
     * @template TClass of Record
     * @param class-string<TClass> $expectedClass
     * @return TClass|null
     */
    private function deserializeRecord(string $prefix, FullyQualifiedName $name, string $expectedClass): ?Record
    {
        $path = $this->pathFor($prefix, $name);
        
        if (!file_exists($path)) {
            return null;
        }
        
        $contents = file_get_contents($path);
        
        try {
            $deserialized = @unserialize($contents);
        } catch (CorruptedRecord $corruption) {
            $this->logger->warning(sprintf(
                'Cache entry file "%s" is corrupted: %s',
                $path,
                $corruption->getMessage()
            ));
            return null;
        }
        
        if (!$deserialized) {
            $this->logger->warning(sprintf(
                'Cache entry file "%s" is empty after deserialization',
                $path
            ));
            return null;
        }
        
        if (!$deserialized instanceof $expectedClass) {
            $this->logger->warning(sprintf(
                'Invalid cache entry file: "%s", got instance of "%s"',
                $path,
                is_object($deserialized) ? get_class($deserialized):gettype($deserialized)
            ));
            return null;
        }
        
        return $deserialized;
    }
}
