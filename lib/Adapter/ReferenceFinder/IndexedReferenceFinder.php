<?php

namespace Phpactor\Indexer\Adapter\ReferenceFinder;

use Generator;
use Phpactor\Indexer\Model\IndexQueryAgent;
use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\RecordReferences;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Model\Record\HasFileReferences;
use Phpactor\ReferenceFinder\ReferenceFinder;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\Location;
use Phpactor\TextDocument\Locations;
use Phpactor\TextDocument\TextDocument;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\WorseReflection\Core\Inference\SymbolContext;
use Phpactor\WorseReflection\Reflector;

class IndexedReferenceFinder implements ReferenceFinder
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

    public function findReferences(TextDocument $document, ByteOffset $byteOffset): Locations
    {
        $symbolContext = $this->reflector->reflectOffset(
            $document->__toString(),
            $byteOffset->toInt()
        )->symbolContext();

        $references = $this->resolveReferences($symbolContext);

        $locations = [];
        foreach ($references as $reference) {
            $locations[] = $reference;
        }

        return new Locations($locations);
    }

    private function resolveReferences(SymbolContext $symbolContext): Generator
    {
        if ($symbolContext->symbol()->symbolType() === Symbol::CLASS_) {
            yield from $this->query->class()->referencesTo($symbolContext->type()->__toString());
            return;
        }

        if ($symbolContext->symbol()->symbolType() === Symbol::FUNCTION) {
            yield from $this->query->function()->referencesTo($symbolContext->type()->__toString());
            return;
        }

        if (in_array($symbolContext->symbol()->symbolType(), [
            Symbol::METHOD,
            Symbol::CONSTANT,
            Symbol::PROPERTY
        ])) {
            yield from $this->query->member()->referencesTo(
                $symbolContext->symbol()->symbolType(),
                $symbolContext->symbol()->name()
            );
            return;
        }
    }
}
