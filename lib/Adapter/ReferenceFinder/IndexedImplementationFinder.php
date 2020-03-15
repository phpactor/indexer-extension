<?php

namespace Phpactor\Indexer\Adapter\ReferenceFinder;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\Index;
use Phpactor\ReferenceFinder\ClassImplementationFinder;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\Location;
use Phpactor\TextDocument\Locations;
use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentUri;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;

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

    public function __construct(Index $index, SourceCodeReflector $reflector)
    {
        $this->index = $index;
        $this->reflector = $reflector;
    }

    /**
     * @return Locations<Location>
     */
    public function findImplementations(TextDocument $document, ByteOffset $byteOffset): Locations
    {
        return new Locations(array_map(function (FullyQualifiedName $name) {
            $record = $this->index->query()->class($name);
            return new Location(
                TextDocumentUri::fromString($record->filePath()),
                $record->start()
            );
        }, $this->index->query()->implementing(
            FullyQualifiedName::fromString(
                $this->reflector->reflectOffset(
                    $document,
                    $byteOffset->toInt()
                )->symbolContext()->type()->__toString()
            )
        )));
    }
}
