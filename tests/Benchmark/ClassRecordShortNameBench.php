<?php

namespace Phpactor\Indexer\Tests\Benchmark;

use Phpactor\Indexer\Adapter\Php\FileSearchIndex;
use Phpactor\Indexer\Adapter\Php\Serialized\FileRepository;
use Phpactor\Indexer\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\Indexer\IndexAgentBuilder;
use Phpactor\Indexer\Model\Matcher\ClassShortNameMatcher;
use Phpactor\Indexer\Model\Query\Criteria\ShortNameBeginsWith;
use Phpactor\Indexer\Model\RecordSerializer\PhpSerializer;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\SearchClient;
use Phpactor\Indexer\Model\SearchIndex;
use Phpactor\Indexer\Model\SearchIndex\ValidatingSearchIndex;

/**
 * @Iterations(33)
 * @Revs(1000)
 * @OutputTimeUnit("microseconds")
 */
class ClassRecordShortNameBench
{
    /**
     * @var ClassRecord
     */
    private $record;

    public function createClassRecord(): void
    {
        $this->record = ClassRecord::fromName('Barfoo\\Foobar');
    }

    /**
     * @BeforeMethods({"createClassRecord"})
     */
    public function benchShortName(): void
    {
        $this->record->shortName();
    }
}
