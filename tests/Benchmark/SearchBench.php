<?php

namespace Phpactor\Indexer\Tests\Benchmark;

use Phpactor\Indexer\Adapter\Php\FileSearchIndex;
use Phpactor\Indexer\Adapter\Php\Serialized\FileRepository;
use Phpactor\Indexer\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\Indexer\IndexAgentBuilder;
use Phpactor\Indexer\Model\Matcher\ClassShortNameMatcher;
use Phpactor\Indexer\Model\Query\Criteria\ShortNameBeginsWith;
use Phpactor\Indexer\Model\RecordSerializer\PhpSerializer;
use Phpactor\Indexer\Model\SearchClient;
use Phpactor\Indexer\Model\SearchIndex;
use Phpactor\Indexer\Model\SearchIndex\ValidatingSearchIndex;



/**
 * Run ./bin/console index:build before running this benchmark
 *
 * @Iterations(10)
 * @Revs(1)
 * @OutputTimeUnit("seconds")
 */
class SearchBench
{
    /**
     * @var SearchClient
     */
    private $search;

    public function createBareFileSearch(): void
    {
        $indexPath = __DIR__ . '/../..';
        $this->search = new FileSearchIndex($indexPath . '/cache/search', new ClassShortNameMatcher());
    }

    public function createFullFileSearch(): void
    {
        $indexPath = __DIR__ . '/../../cache';
        $this->search = IndexAgentBuilder::create(
            $indexPath,
            __DIR__ .'/../../'
        )
            ->buildAgent()->search();
    }

    /**
     * @BeforeMethods({"createBareFileSearch"})
     * @ParamProviders({"provideSearches"})
     */
    public function benchBareFileSearch(array $params): void
    {
        foreach ($this->search->search(new ShortNameBeginsWith($params['search'])) as $result) {
        }
    }

    /**
     * @BeforeMethods({"createFullFileSearch"})
     * F
     * @ParamProviders({"provideSearches"})
     */
    public function benchFullFileSearch(array $params): void
    {
        foreach ($this->search->search(new ShortNameBeginsWith($params['search'])) as $result) {
        }
    }

    public function provideSearches()
    {
        yield 'A' => [
            'search' => 'A',
        ];

        yield 'Request' => [
            'search' => 'Request',
        ];
    }
}
