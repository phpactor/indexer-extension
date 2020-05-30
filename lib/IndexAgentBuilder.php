<?php

namespace Phpactor\Indexer;

use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\Filesystem\Adapter\Simple\SimpleFileListProvider;
use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\Indexer\Adapter\Filesystem\FilesystemFileListProvider;
use Phpactor\Indexer\Adapter\Php\FileSearchIndex;
use Phpactor\Indexer\Adapter\Php\Serialized\FileRepository;
use Phpactor\Indexer\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexBuilder;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Indexer\Model\FileListProvider;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\RealIndexAgent;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Model\IndexQueryAgent;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Model\Matcher;
use Phpactor\Indexer\Model\Matcher\ClassShortNameMatcher;
use Phpactor\Indexer\Model\RecordReferenceEnhancer;
use Phpactor\Indexer\Model\RecordReferenceEnhancer\NullRecordReferenceEnhancer;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Indexer\Model\SearchIndex;
use Phpactor\Indexer\Model\SearchIndex\FilteredSearchIndex;
use Phpactor\Indexer\Model\TestIndexAgent;

class IndexAgentBuilder
{
    /**
     * @var string
     */
    private $indexRoot;

    /**
     * @var RecordReferenceEnhancer
     */
    private $enhancer;

    /**
     * @var array<string>
     */
    private $includePatterns = [
        '/**/*.php',
    ];

    /**
     * @var array<string>
     */
    private $excludePatterns = [
    ];

    /**
     * @var string
     */
    private $projectRoot;

    /**
     * @var array<TolerantIndexer>|null
     */
    private $indexers = null;

    private function __construct(string $indexRoot, string $projectRoot)
    {
        $this->indexRoot = $indexRoot;
        $this->enhancer = new NullRecordReferenceEnhancer();
        $this->projectRoot = $projectRoot;
    }

    public static function create(string $indexRootPath, string $projectRoot): self
    {
        return new self($indexRootPath, $projectRoot);
    }

    public function setReferenceEnhancer(RecordReferenceEnhancer $enhancer): self
    {
        $this->enhancer = $enhancer;

        return $this;
    }

    public function build(): IndexAgent
    {
        return $this->buildTestAgent();
    }

    public function buildTestAgent(): TestIndexAgent
    {
        $search = $this->buildSearch();
        $index = $this->buildIndex($search);
        $query = $this->buildQuery($index);
        $builder = $this->buildBuilder($index);
        $indexer = $this->buildIndexer($builder, $index);

        return new RealIndexAgent($index, $query, $search, $indexer);
    }

    private function buildIndex(SearchIndex $search): Index
    {
        $repository = new FileRepository($this->indexRoot);

        return new SerializedIndex($repository, $search);
    }

    private function buildQuery(Index $index): IndexQueryAgent
    {
        return new IndexQueryAgent(
            $index,
            $this->enhancer
        );
    }

    private function buildSearch(): SearchIndex
    {
        $search = new FileSearchIndex($this->indexRoot . '/search', $this->buildMatcher());
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
        if (null !== $this->indexers) {
            return new TolerantIndexBuilder($index, $this->indexers);
        }
        return TolerantIndexBuilder::create($index);
    }

    private function buildIndexer(IndexBuilder $builder, Index $index): Indexer
    {
        return new Indexer($builder, $index, $this->buildFileListProvider());
    }

    private function buildFileListProvider(): FileListProvider
    {
        return new FilesystemFileListProvider(
            $this->buildFilesystem(),
            $this->includePatterns,
            $this->excludePatterns
        );
    }

    private function buildFilesystem(): SimpleFilesystem
    {
        return new SimpleFilesystem(
            $this->indexRoot,
            new SimpleFileListProvider(FilePath::fromString($this->projectRoot))
        );
    }

    /**
     * @param array<TolerantIndexer> $indexers
     */
    public function setIndexers(array $indexers): self
    {
        $this->indexers = $indexers;

        return $this;
    }
}
