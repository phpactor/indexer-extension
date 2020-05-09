<?php

namespace Phpactor\Indexer\Tests\Integration\Tolerant\Indexer;

use Generator;
use PHPUnit\Framework\TestCase;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\ClassLikeReferenceIndexer;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\MemberIndexer;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexBuilder;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Model\MemberReference;
use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Tests\IntegrationTestCase;
use SplFileInfo;

class MemberIndexerTest extends IntegrationTestCase
{
    /**
     * @dataProvider provideStaticMembers
     */
    public function testMembers(string $manifest, MemberReference $memberReference, int $expectedCount): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest($manifest);
        $index = $this->createIndex();
        $indexBuilder = new TolerantIndexBuilder($index, [
            new MemberIndexer()
        ]);
        $fileList = $this->fileListProvider('src');
        $indexer = new Indexer($indexBuilder, $index, $fileList);
        $indexer->getJob()->run();

        $memberRecord = $index->get(MemberRecord::fromMemberReference($memberReference));

        foreach ($memberRecord->references() as $reference) {
            $fileRecord = $index->get(FileRecord::fromPath($reference));
            $candidates = $fileRecord->memberCandidatesFor($memberRecord);
        }

        self::assertCount($expectedCount, $candidates);
    }

    /**
     * @return Generator<mixed>
     */
    public function provideStaticMembers(): Generator
    {
        yield [
            "// File: src/file1.php\n<?php Foobar::static()",
            MemberReference::create('method', 'Foobar', 'static'),
            1
        ];
    }
}
