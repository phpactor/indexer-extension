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
use Phpactor\Indexer\Model\Record\MemberRecord;
use Phpactor\Indexer\Tests\IntegrationTestCase;
use Phpactor\Indexer\Tests\Integration\Tolerant\TolerantIndexerTestCase;
use SplFileInfo;

class MemberIndexerTest extends TolerantIndexerTestCase
{
    public function testRemovesIncomingReferences(): void
    {
        $indexer = new MemberIndexer();
        $index = $this->createIndex();
        $record1 = $index->get(MemberRecord::fromMemberReference(MemberReference::create('method', 'Foobar', 'bar')));
        $record2 = $index->get(MemberRecord::fromMemberReference(MemberReference::create('method', 'Foobar', 'bar')));

        $this->assertRemovesIncomingReferences($indexer, $index, $record1, $record2);
    }

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
        assert($memberRecord instanceof MemberRecord);

        $positionedReferences = [];
        foreach ($memberRecord->references() as $reference) {
            $fileRecord = $index->get(FileRecord::fromPath($reference));
            assert($fileRecord instanceof FileRecord);
            foreach ($fileRecord->referencesTo($memberRecord) as $positionedReference) {
                $positionedReferences[] = $positionedReference;
            }
        }

        self::assertCount($expectedCount, $positionedReferences);
    }

    /**
     * @return Generator<mixed>
     */
    public function provideStaticMembers(): Generator
    {
        yield 'single ref' => [
            "// File: src/file1.php\n<?php Foobar::static()",
            MemberReference::create('method', 'Foobar', 'static'),
            1
        ];

        yield 'multiple ref' => [
            "// File: src/file1.php\n<?php Foobar::static(); Foobar::static();",
            MemberReference::create('method', 'Foobar', 'static'),
            2
        ];

        yield 'constant' => [
            "// File: src/file1.php\n<?php Foobar::FOOBAR;",
            MemberReference::create('constant', 'Foobar', 'FOOBAR'),
            1
        ];

        yield 'property' => [
            "// File: src/file1.php\n<?php Foobar::\$foobar;",
            MemberReference::create('property', 'Foobar', '$foobar'),
            1
        ];
    }
}
