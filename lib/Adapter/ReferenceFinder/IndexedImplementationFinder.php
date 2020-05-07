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
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\WorseReflection\Core\Inference\SymbolContext;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use Phpactor\WorseReflection\Reflector;

class IndexedImplementationFinder implements ClassImplementationFinder
{
    /**
     * @var Index
     */
    private $index;

    /**
     * @var Reflector
     */
    private $reflector;

    public function __construct(Index $index, Reflector $reflector)
    {
        $this->index = $index;
        $this->reflector = $reflector;
    }

    /**
     * @return Locations<Location>
     */
    public function findImplementations(TextDocument $document, ByteOffset $byteOffset): Locations
    {
        $symbolContext = $this->reflector->reflectOffset(
            $document,
            $byteOffset->toInt()
        )->symbolContext();

        if ($symbolContext->symbol()->symbolType() === Symbol::METHOD) {
            return $this->methodImplementations($symbolContext);
        }

        return new Locations(array_map(function (FullyQualifiedName $name) {
            $record = $this->index->query()->class($name);

            return new Location(
                TextDocumentUri::fromString($record->filePath()),
                $record->start()
            );
        }, $this->index->query()->implementing(
            FullyQualifiedName::fromString(
                $symbolContext->type()->__toString()
            )
        )));
    }

    /**
     * @return Locations<Location>
     */
    private function methodImplementations(SymbolContext $symbolContext): Locations
    {
        $container = $symbolContext->containerType();

        $implementations = $this->index->query()->implementing(
            FullyQualifiedName::fromString(
                $container->__toString()
            )
        );

        $methodName = $symbolContext->symbol()->name();
        $locations = [];

        foreach ($implementations as $implementation) {
            $record = $this->index->query()->class($implementation);
            $reflection = $this->reflector->reflectClassLike($implementation->__toString());
            try {
                $method = $reflection->methods()->get($methodName);
            } catch (NotFound $notFound) {
                continue;
            }

             $locations[] = Location::fromPathAndOffset(
                $record->filePath(),
                $method->position()->start()
            );
        }

        return new Locations($locations);
    }
}
