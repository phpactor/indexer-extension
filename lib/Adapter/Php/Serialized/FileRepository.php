<?php

namespace Phpactor\ProjectQuery\Adapter\Php\Serialized;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\ProjectQuery\Model\Record\ClassRecord;
use RuntimeException;

class FileRepository
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->ensureDirectoryExists($path);
    }

    public function putClass(ClassRecord $class): void
    {
        $path = $this->pathFor($class->fqn());
        if (file_put_contents($path, serialize($class))) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Could not write to file "%s"', $path
        ));
    }

    public function getClass(FullyQualifiedName $name): ?ClassRecord
    {
        $path = $this->pathFor($name);

        if (!file_exists($path)) {
            return null;
        }

        if (!$contents = file_get_contents($path)) {
            throw new RuntimeException(sprintf('Could not read file "%s"', $path));
        }

        $deserialized = unserialize($contents);

        if (!$deserialized) {
            throw new RuntimeException(sprintf('Could not deserialize file "%s"', $path));
        }

        return $deserialized;
    }

    private function pathFor(FullyQualifiedName $class): string
    {
        return sprintf('%s/%s.cache', $this->path, md5($class->__toString()));
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (file_exists($path)) {
            return;
        }

        if (mkdir($path)) {
            return;
        }

        throw new RuntimeException(sprintF(
            'Could not create index directory "%s"',
            $path
        ));
    }
}
