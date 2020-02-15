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
