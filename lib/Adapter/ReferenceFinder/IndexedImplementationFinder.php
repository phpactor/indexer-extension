<?php

namespace Phpactor\Indexer\Adapter\ReferenceFinder;

use Phpactor\Indexer\Model\IndexQueryAgent;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\ReferenceFinder\ClassImplementationFinder;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\Location;
use Phpactor\TextDocument\Locations;
use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentUri;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\WorseReflection\Core\Inference\SymbolContext;
use Phpactor\WorseReflection\Reflector;

class IndexedImplementationFinder implements ClassImplementationFinder
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var IndexQueryAgent
     */
    private $query;

    public function __construct(IndexQueryAgent $query, Reflector $reflector)
    {
        $this->reflector = $reflector;
        $this->query = $query;
    }

    /**
     * @return Locations<Location>
     */
    public function findImplementations(TextDocument $document, ByteOffset $byteOffset): Locations
    {
        $symbolContext = $this->reflector->reflectOffset(
            $document->__toString(),
            $byteOffset->toInt()
        )->symbolContext();

        if ($symbolContext->symbol()->symbolType() === Symbol::METHOD) {
            return $this->methodImplementations($symbolContext);
        }

        return new Locations(array_map(function (FullyQualifiedName $name) {
            $record = $this->query->class()->get($name);

            return new Location(
                TextDocumentUri::fromString($record->filePath()),
                $record->start()
            );
        }, $this->query->class()->implementing(
            $symbolContext->type()->__toString()
        )));
    }

    /**
     * @return Locations<Location>
     */
    private function methodImplementations(SymbolContext $symbolContext): Locations
    {
        $container = $symbolContext->containerType();

        if (null === $container) {
            return new Locations([]);
        }

        $implementations = $this->query->class()->implementing(
            $container->__toString()
        );

        $methodName = $symbolContext->symbol()->name();
        $locations = [];

        foreach ($implementations as $implementation) {
            $record = $this->query->class()->get($implementation);
            try {
                $reflection = $this->reflector->reflectClassLike($implementation->__toString());
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
