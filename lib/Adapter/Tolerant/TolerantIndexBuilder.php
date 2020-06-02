<?php

namespace Phpactor\Indexer\Adapter\Tolerant;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Parser;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\ClassDeclarationIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\ClassLikeReferenceIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\FunctionDeclarationIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\FunctionReferenceIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\InterfaceDeclarationIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\MemberIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\TraitDeclarationIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\TraitUseClauseIndexer;
use Phpactor\Indexer\Model\Exception\CannotIndexNode;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SplFileInfo;

final class TolerantIndexBuilder implements IndexBuilder
{
    /**
     * @var Index
     */
    private $index;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var TolerantIndexer[]
     */
    private $indexers;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param TolerantIndexer[] $indexers
     */
    public function __construct(
        Index $index,
        array $indexers,
        LoggerInterface $logger,
        ?Parser $parser = null
    ) {
        $this->index = $index;
        $this->parser = $parser ?: new Parser();
        $this->indexers = $indexers;
        $this->logger = $logger;
    }

    public static function create(Index $index, ?LoggerInterface $logger = null): self
    {
        return new self(
            $index,
            [
                new ClassDeclarationIndexer(),
                new FunctionDeclarationIndexer(),
                new InterfaceDeclarationIndexer(),
                new TraitDeclarationIndexer(),
                new TraitUseClauseIndexer(),
                new ClassLikeReferenceIndexer(),
                new FunctionReferenceIndexer(),
                new MemberIndexer(),
            ],
            $logger ?: new NullLogger()
        );
    }

    public function index(SplFileInfo $info): void
    {
        $contents = @file_get_contents($info->getPathname());

        if (false === $contents) {
            return;
        }

        foreach ($this->indexers as $indexer) {
            $indexer->beforeParse($this->index, $info);
        }

        $node = $this->parser->parseSourceFile($contents, $info->getPathname());
        $this->indexNode($info, $node);
    }

    public function done(): void
    {
        $this->index->done();
    }

    private function indexNode(SplFileInfo $info, Node $node): void
    {
        foreach ($this->indexers as $indexer) {
            try {
                if ($indexer->canIndex($node)) {
                    $indexer->index($this->index, $info, $node);
                }
            } catch (CannotIndexNode $cannotIndexNode) {
                $this->logger->warning(sprintf(
                    'Cannot index node of class "%s" in file "%s": %s',
                    get_class($node),
                    $info->getPathname(),
                    $cannotIndexNode->getMessage()
                ));
            }
        }

        foreach ($node->getChildNodes() as $childNode) {
            $this->indexNode($info, $childNode);
        }
    }
}
