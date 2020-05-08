<?php

namespace Phpactor\Indexer\Tests\Unit\Adapter\Tolerant\Indexer;

use PHPUnit\Framework\TestCase;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\ClassLikeReferenceIndexer;
use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Tests\IntegrationTestCase;
use SplFileInfo;

class ClassLikeReferenceIndexerTest extends IntegrationTestCase
{
    public function testRemovesIncomingReferences(): void
    {
        $indexer = new ClassLikeReferenceIndexer();
        $index = $this->createIndex();

        $subject = FileRecord::fromPath($this->workspace()->path('test.php'));

        $record1 = $index->get(ClassRecord::fromName('foobar'));
        $record2 = $index->get(ClassRecord::fromName('barfoo'));

        $index->write($record1->addReference($subject->filePath()));
        $index->write($record2->addReference($subject->filePath()));

        $subject->addReference(RecordReference::fromRecordAndOffset($record1, 12));
        $subject->addReference(RecordReference::fromRecordAndOffset($record2, 32));

        $index->write($subject);

        self::assertCount(1, $record1->references());
        self::assertCount(1, $record2->references());

        $indexer->beforeParse($index, new SplFileInfo($subject->filePath()));

        $record1 = $index->get(ClassRecord::fromName('foobar'));
        $record2 = $index->get(ClassRecord::fromName('barfoo'));

        self::assertCount(0, $record1->references());
        self::assertCount(0, $record2->references());

    }
}
