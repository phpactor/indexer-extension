<?php

namespace Phpactor\Indexer\Tests\Unit\Model\SearchIndex;

use PHPUnit\Framework\TestCase;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Indexer\Model\SearchIndex;
use Phpactor\Indexer\Model\SearchIndex\FilteredSearchIndex;
use Phpactor\Indexer\Tests\IntegrationTestCase;
use Prophecy\Argument;

class FilteredSearchIndexTest extends IntegrationTestCase
{
    /**
     * @var ObjectProphecy
     */
    private $innerIndex;

    /**
     * @var FilteredSearchIndex
     */
    private $index;

    protected function setUp(): void
    {
        $this->innerIndex = $this->prophesize(SearchIndex::class);
        $this->index = new FilteredSearchIndex($this->innerIndex->reveal(), [ClassRecord::RECORD_TYPE]);
    }

    public function testDecoration(): void
    {
        $this->innerIndex->search('foobar')->willYield([ClassRecord::fromName('Foobar')])->shouldBeCalled();
        $this->innerIndex->flush()->shouldBeCalled();
        $this->index->search('foobar');
        $this->index->flush();
    }

    public function testWritesRecordThatIsAllowed(): void
    {
        $this->innerIndex->write(ClassRecord::fromName('FOOBAR'))->shouldBeCalled();
        $this->index->write(ClassRecord::fromName('FOOBAR'));
    }

    public function testDoesNotWriteRecordsNotAllowed(): void
    {
        $this->innerIndex->write(Argument::any())->shouldNotBeCalled();
        $this->index->write(FunctionRecord::fromName('FOOBAR'));
    }
}
