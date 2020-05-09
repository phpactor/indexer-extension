<?php

namespace Phpactor\Indexer\Tests\Integration\Tolerant\Indexer;

use PHPUnit\Framework\TestCase;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\ClassLikeReferenceIndexer;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Model\Record\HasFileReferences;
use Phpactor\Indexer\Model\TRecord;
use Phpactor\Indexer\Tests\IntegrationTestCase;
use Phpactor\Indexer\Tests\Integration\Tolerant\TolerantIndexerTestCase;
use SplFileInfo;

class ClassLikeReferenceIndexerTest extends TolerantIndexerTestCase
{
    public function testRemovesIncomingReferences(): void
    {
        $indexer = new ClassLikeReferenceIndexer();
        $index = $this->createIndex();
        $record1 = $index->get(ClassRecord::fromName('foobar'));
        $record2 = $index->get(ClassRecord::fromName('barfoo'));

        $this->assertRemovesIncomingReferences($indexer, $index, $record1, $record2);
    }
}
