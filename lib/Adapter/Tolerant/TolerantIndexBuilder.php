<?php

namespace Phpactor\Indexer\Adapter\Tolerant;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Parser;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\ClassDeclarationIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\FunctionDeclarationIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\InterfaceDeclarationIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\TraitDeclarationIndexer;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use SplFileInfo;

class TolerantIndexBuilder implements IndexBuilder
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

    public function __construct(
        Index $index,
        ?Parser $parser = null
    ) {
        $this->index = $index;
        $this->parser = $parser ?: new Parser();
        $this->indexers = [
            new ClassDeclarationIndexer(),
            new FunctionDeclarationIndexer(),
            new InterfaceDeclarationIndexer(),
            new TraitDeclarationIndexer(),
        ];
    }

    public function index(SplFileInfo $info): void
    {
        $contents = @file_get_contents($info->getPathname());

        if (false === $contents) {
            return;
        }

        $node = $this->parser->parseSourceFile($contents, $info->getPathname());
        $this->indexNode($info, $node);
    }

    public function done(): void
    {
        $this->index->updateTimestamp();
    }

    private function indexNode(SplFileInfo $info, Node $node): void
    {
        foreach ($this->indexers as $indexer) {
            if ($indexer->canIndex($node)) {
                $indexer->index($this->index, $info, $node);
            }
        }

        foreach ($node->getChildNodes() as $childNode) {
            $this->indexNode($info, $childNode);
        }
    }
}
