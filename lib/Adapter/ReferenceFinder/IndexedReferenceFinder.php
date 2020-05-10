<?php

namespace Phpactor\Indexer\Adapter\ReferenceFinder;

use Phpactor\Indexer\Model\IndexQueryAgent;
use Phpactor\Indexer\Model\Record;
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

        $record = $this->resolveRecord($symbolContext);

        if ($record === null) {
            return new Locations([]);
        }

        $locations = [];

        assert($record instanceof HasFileReferences);

        foreach ($record->references() as $reference) {
            $fileRecord = $this->query->file()->get($reference);
            assert($fileRecord instanceof FileRecord);
            $references = $fileRecord->referencesTo($record);

            foreach ($references as $reference) {
                $locations[] = Location::fromPathAndOffset($fileRecord->filePath(), $reference->offset());
            }
        }

        return new Locations($locations);
    }

    private function resolveRecord(SymbolContext $symbolContext): ?Record
    {
        if ($symbolContext->symbol()->symbolType() === Symbol::CLASS_) {
            return $this->query->class()->get($symbolContext->type()->__toString());
        }

        if ($symbolContext->symbol()->symbolType() === Symbol::FUNCTION) {
            return $this->query->function()->get($symbolContext->type()->__toString());
        }

        if (in_array($symbolContext->symbol()->symbolType(), [
            Symbol::METHOD,
            Symbol::CONSTANT,
            Symbol::PROPERTY
        ])) {
            return $this->query->member()->getByTypeAndName(
                $symbolContext->symbol()->symbolType(),
                $symbolContext->symbol()->name()
            );
        }

        return null;
    }
}
