<?php

namespace Phpactor\Indexer\Tests\Integration\Tolerant\Indexer;

use Phpactor\Indexer\Adapter\Tolerant\Indexer\ClassLikeReferenceIndexer;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Tests\Integration\Tolerant\TolerantIndexerTestCase;

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
