<?php

namespace Phpactor\WorkspaceQuery\Adapter\ReferenceFinder;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\WorkspaceQuery\Model\Index;
use Phpactor\ReferenceFinder\ClassImplementationFinder;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\Location;
use Phpactor\TextDocument\Locations;
use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentUri;
use Phpactor\WorkspaceQuery\Model\IndexUpdater;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use RuntimeException;

class IndexedImplementationFinder implements ClassImplementationFinder
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
     * @var IndexUpdater
     */
    private $indexUpdater;

    public function __construct(Index $index, IndexUpdater $indexUpdater, SourceCodeReflector $reflector)
    {
        $this->index = $index;
        $this->reflector = $reflector;
        $this->indexUpdater = $indexUpdater;
    }

    /**
     * @return Locations<Location>
     */
    public function findImplementations(TextDocument $document, ByteOffset $byteOffset): Locations
    {
        if (false === $this->index->exists()) {
            throw new RuntimeException(sprintf(
                'The index must be initialized. Run "%s index:refresh" on the CLI',
                $_SERVER['SCRIPT_FILENAME']
            ));
        }

        $this->indexUpdater->build();

        $offset = $this->reflector->reflectOffset($document, $byteOffset->toInt());

        $type = $offset->symbolContext()->type();
        $implementations = $this->index->query()->implementing(
            FullyQualifiedName::fromString($type->__toString())
        );

        return new Locations(array_map(function (FullyQualifiedName $name) {
            $record = $this->index->query()->class($name);
            return new Location(
                TextDocumentUri::fromString($record->filePath()),
                $record->start()
            );
        }, $implementations));
    }
}
