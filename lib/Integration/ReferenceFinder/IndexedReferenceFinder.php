<?php

namespace Phpactor\Indexer\Integration\ReferenceFinder;

use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Indexer\Model\Index;
use Phpactor\ReferenceFinder\ReferenceFinder;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\Location;
use Phpactor\TextDocument\Locations;
use Phpactor\TextDocument\TextDocument;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\WorseReflection\Core\Inference\SymbolContext;
use Phpactor\WorseReflection\Reflector;
use RuntimeException;

class IndexedReferenceFinder implements ReferenceFinder
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

        assert($record instanceof ClassRecord || $record instanceof FunctionRecord);
        foreach ($record->references() as $reference) {
            $fileRecord = $this->index->get(FileRecord::fromPath($reference));
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
            return $this->index->get(ClassRecord::fromName($symbolContext->type()->__toString()));
        }

        if ($symbolContext->symbol()->symbolType() === Symbol::FUNCTION) {
            return $this->index->get(FunctionRecord::fromName($symbolContext->symbol()->name()));
        }

        return null;
    }
}
