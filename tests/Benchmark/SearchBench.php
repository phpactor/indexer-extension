<?php

namespace Phpactor\Indexer\Tests\Benchmark;

use Phpactor\Indexer\Adapter\Php\FileSearchIndex;
use Phpactor\Indexer\Model\Matcher\ClassShortNameMatcher;

/**
 * @Iterations(10)
 * @Revs(1)
 * @OutputTimeUnit("milliseconds")
 */
class SearchBench
{
    /**
     * @var FileSearchIndex
     */
    private $search;

    /**
     * Run ./bin/console index:build before running this benchmark
     */
    public function setUp(): void
    {
        $this->search = new FileSearchIndex(__DIR__ . '/../../cache/search', new ClassShortNameMatcher());
    }

    /**
     * @BeforeMethods({"setUp"})
     * @ParamProviders({"provideSearches"})
     */
    public function benchSearch(array $params): void
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

        yield 'C' => [
            'search' => 'C',
        ];

        yield 'Request' => [
            'search' => 'Request',
        ];
    }
}
