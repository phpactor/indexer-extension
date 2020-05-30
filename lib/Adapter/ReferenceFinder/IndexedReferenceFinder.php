<?php

namespace Phpactor\Indexer\Adapter\ReferenceFinder;

use Generator;
use Phpactor\Indexer\Model\QueryClient;
use Phpactor\Indexer\Model\LocationConfidence;
use Phpactor\ReferenceFinder\PotentialLocation;
use Phpactor\ReferenceFinder\ReferenceFinder;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\WorseReflection\Core\Exception\NotFound;
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

    public function __construct(QueryClient $query, Reflector $reflector)
    {
        $this->reflector = $reflector;
        $this->query = $query;
    }

    /**
     * @return Generator<PotentialLocation>
     */
    public function findReferences(TextDocument $document, ByteOffset $byteOffset): Generator
    {
        try {
            $symbolContext = $this->reflector->reflectOffset(
                $document->__toString(),
                $byteOffset->toInt()
            )->symbolContext();
        } catch (NotFound $notFound) {
            return;
        }

        foreach ($this->resolveReferences($symbolContext) as $locationConfidence) {
            if ($locationConfidence->isSurely()) {
                yield PotentialLocation::surely($locationConfidence->location());
                continue;
            }

            if ($locationConfidence->isMaybe()) {
                yield PotentialLocation::maybe($locationConfidence->location());
                continue;
            }

            yield PotentialLocation::not($locationConfidence->location());
        }
    }

    /**
     * @return Generator<LocationConfidence>
     */
    private function resolveReferences(SymbolContext $symbolContext): Generator
    {
        $symbolType = $symbolContext->symbol()->symbolType();
        if ($symbolType === Symbol::CLASS_) {
            yield from $this->query->class()->referencesTo($symbolContext->type()->__toString());
            return;
        }

        if ($symbolType === Symbol::FUNCTION) {
            yield from $this->query->function()->referencesTo($symbolContext->symbol()->name());
            return;
        }

        if (in_array($symbolContext->symbol()->symbolType(), [
            Symbol::METHOD,
            Symbol::CONSTANT,
            Symbol::PROPERTY
        ])) {
            yield from $this->query->member()->referencesTo(
                $symbolContext->symbol()->symbolType(),
                $symbolContext->symbol()->name(),
                $symbolContext->containerType()
            );
            return;
        }
    }
}
