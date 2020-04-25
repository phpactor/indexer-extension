<?php

namespace Phpactor\Indexer\Adapter\Tolerant;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Microsoft\PhpParser\Node\Statement\FunctionDeclaration;
use Microsoft\PhpParser\Node\Statement\InterfaceDeclaration;
use Microsoft\PhpParser\Parser;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\ClassDeclarationIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\FunctionDeclarationIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\InterfaceDeclarationIndexer;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\TextDocument\ByteOffset;
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

    private function indexFunction(SplFileInfo $info, FunctionDeclaration $node): void
    {
        $record = $this->index->get(FunctionRecord::fromName($node->getNamespacedName()->getFullyQualifiedNameText()));
        assert($record instanceof FunctionRecord);
        $record->setLastModified($info->getCTime());
        $record->setStart(ByteOffset::fromInt($node->getStart()));
        $record->setFilePath($info->getPathname());
        $this->index->write($record);
    }

}
