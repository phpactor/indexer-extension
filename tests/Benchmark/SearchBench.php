<?php

namespace Phpactor\Indexer\Tests\Benchmark;

use Phpactor\Indexer\Adapter\Php\FileSearchIndex;
use Phpactor\Indexer\Adapter\Php\Serialized\FileRepository;
use Phpactor\Indexer\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\Indexer\Model\Matcher\ClassShortNameMatcher;
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
     * @var SearchIndex
     */
    private $search;

    public function createFileSearch(): void
    {
        $indexPath = __DIR__ . '/../..';
        $this->search = new FileSearchIndex($indexPath . '/cache/search', new ClassShortNameMatcher());
    }

    public function createValidatingFileSearch(): void
    {
        $indexPath = __DIR__ . '/../../cache';
        $this->search = new ValidatingSearchIndex(
            new FileSearchIndex($indexPath . '/search', new ClassShortNameMatcher()),
            new SerializedIndex(new FileRepository($indexPath))
        );
    }

    /**
     * @BeforeMethods({"createFileSearch"})
     * @ParamProviders({"provideSearches"})
     */
    public function benchFileSearch(array $params): void
    {
        foreach ($this->search->search($params['search']) as $result) {
        }
    }

    /**
     * @BeforeMethods({"createValidatingFileSearch"})
     * @ParamProviders({"provideSearches"})
     */
    public function benchValidatingFileSearch(array $params): void
    {
        foreach ($this->search->search($params['search']) as $result) {
        }
    }

    public function provideSearches()
    {
        yield 'A' => [
            'search' => 'A',
        ];

        yield 'B' => [
            'search' => 'B',
        ];

        yield 'Request' => [
            'search' => 'Request',
        ];
    }
}
