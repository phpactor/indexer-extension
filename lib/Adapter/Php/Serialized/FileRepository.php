<?php

namespace Phpactor\Indexer\Adapter\Php\Serialized;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\Record\ClassRecord;
use RuntimeException;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\mkdir;

class FileRepository
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $lastUpdate;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->initializeLastUpdate();
    }

    public function putClass(ClassRecord $class): void
    {
        $path = $this->pathFor($class->fqn());
        $this->ensureDirectoryExists(dirname($path));
        file_put_contents($path, serialize($class));
    }

    public function getClass(FullyQualifiedName $name): ?ClassRecord
    {
        $path = $this->pathFor($name);

        if (!file_exists($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        $deserialized = unserialize($contents);

        if (!$deserialized) {
            throw new RuntimeException(sprintf('Could not deserialize file "%s"', $path));
        }

        return $deserialized;
    }

    private function pathFor(FullyQualifiedName $class): string
    {
        $hash = md5($class->__toString());
        return sprintf(
            '%s/%s/%s/%s.cache',
            $this->path,
            substr($hash, 0, 1),
            substr($hash, 1, 1),
            $hash
        );
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
}
