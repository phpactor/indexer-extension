<?php

namespace Phpactor\Indexer\Tests\Integration\Tolerant;

use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexer;
use Phpactor\Indexer\Model\Record\HasFileReferences;
use Phpactor\Indexer\Tests\IntegrationTestCase;
use SplFileInfo;

class TolerantIndexerTestCase extends IntegrationTestCase
{
    /**
     * @param HasFileReferences&Record $record1
     * @param HasFileReferences&Record $record2
     */
    protected function assertRemovesIncomingReferences(TolerantIndexer $indexer, Index $index, Record $record1, Record $record2): void
    {
        $this->workspace()->reset();
        $subject = FileRecord::fromPath($this->workspace()->path('test.php'));
        
        $index->write($record1->addReference($subject->filePath()));
        $index->write($record2->addReference($subject->filePath()));
        
        $subject->addReference(RecordReference::fromRecordAndOffset($record1, 12));
        $subject->addReference(RecordReference::fromRecordAndOffset($record2, 32));
        
        $index->write($subject);
        
        self::assertCount(1, $record1->references());
        self::assertCount(1, $record2->references());
        
        $indexer->beforeParse($index, new SplFileInfo($subject->filePath()));
        
        $record1 = $index->get($record1);
        $record2 = $index->get($record2);
        
        /** @phpstan-ignore-next-line */
        self::assertCount(0, $record1->references());
        /** @phpstan-ignore-next-line */
        self::assertCount(0, $record2->references());
    }
}
