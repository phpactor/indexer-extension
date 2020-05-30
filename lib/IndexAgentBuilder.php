<?php

namespace Phpactor\Indexer;

use Phpactor\Indexer\Adapter\Php\FileSearchIndex;
use Phpactor\Indexer\Adapter\Php\Serialized\FileRepository;
use Phpactor\Indexer\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexBuilder;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Model\IndexQueryAgent;
use Phpactor\Indexer\Model\Matcher;
use Phpactor\Indexer\Model\Matcher\ClassShortNameMatcher;
use Phpactor\Indexer\Model\RecordReferenceEnhancer;
use Phpactor\Indexer\Model\RecordReferenceEnhancer\NullRecordReferenceEnhancer;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Indexer\Model\SearchIndex;
use Phpactor\Indexer\Model\SearchIndex\FilteredSearchIndex;

class IndexAgentBuilder
{
    /**
     * @var string
     */
    private $indexPath;

    /**
     * @var RecordReferenceEnhancer
     */
    private $enhancer;

    public function __construct(string $indexPath)
    {
        $this->indexPath = $indexPath;
        $this->enhancer = new NullRecordReferenceEnhancer();
    }

    public function setReferenceEnhancer(RecordReferenceEnhancer $enhancer): self
    {
        $this->enhancer = $enhancer;

        return $this;
    }

    public function build(): IndexAgent
    {
        $index = $this->buildIndex();
        $query = $this->buildQuery($index);
        $search = $this->buildSearch($index);
        $builder = $this->buildBuilder($index);

        return new IndexAgent($query, $search, $builder);
    }

    private function buildIndex(): Index
    {
        $repository = new FileRepository($this->indexPath);
        return new SerializedIndex($repository);
    }

    private function buildQuery(Index $index): IndexQueryAgent
    {
        return new IndexQueryAgent(
            $index,
            $this->enhancer
        );
    }

    private function buildSearch(Index $index): SearchIndex
    {
        $search = new FileSearchIndex($this->indexPath, $this->buildMatcher());
        $search = new FilteredSearchIndex($search, [
            ClassRecord::RECORD_TYPE,
            FunctionRecord::RECORD_TYPE,
        ]);

        return $search;
    }

    private function buildMatcher(): Matcher
    {
        return new ClassShortNameMatcher();
    }

    private function buildBuilder(Index $index): IndexBuilder
    {
        return TolerantIndexBuilder::create($index);
    }
}
