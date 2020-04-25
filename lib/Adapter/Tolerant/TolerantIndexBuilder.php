<?php

namespace Phpactor\Indexer\Adapter\Tolerant;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Microsoft\PhpParser\Parser;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Model\Record\ClassRecord;
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

    public function __construct(
        Index $index,
        ?Parser $parser = null
    ) {
        $this->index = $index;
        $this->parser = $parser ?: new Parser();
    }

    public function index(SplFileInfo $info): void
    {
        $contents = @file_get_contents($info->getPathname());

        if (false === $contents) {
            return;
        }

        foreach ($this->parser->parseSourceFile($contents, $info->getPathname())->getDescendantNodes() as $node) {
            $this->indexNode($info, $node);

        }
    }

    public function done(): void
    {
        $this->index->updateTimestamp();
    }

    private function indexNode(SplFileInfo $info, Node $node): void
    {
        if ($node instanceof ClassDeclaration) {
            $this->indexClassDeclaration($info, $node);
            return;
        }
    }

    private function indexClassDeclaration(SplFileInfo $info, ClassDeclaration $node): void
    {
        $record = $this->index->get(ClassRecord::fromName($node->getNamespacedName()->getFullyQualifiedNameText()));
        assert($record instanceof ClassRecord);
        $record->withLastModified($info->getCTime());
        $record->withStart(ByteOffset::fromInt($node->getStart()));
        $record->withType('class');
        $record->withFilePath($info->getPathname());

        $this->index->write($record);
    }
}
