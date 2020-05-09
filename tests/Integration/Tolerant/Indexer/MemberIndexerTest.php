<?php

namespace Phpactor\Indexer\Tests\Integration\Tolerant\Indexer;

use Generator;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\MemberIndexer;
use Phpactor\Indexer\Adapter\Tolerant\TolerantIndexBuilder;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Model\MemberReference;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Model\Record\MemberRecord;
use Phpactor\Indexer\Tests\Integration\Tolerant\TolerantIndexerTestCase;

class MemberIndexerTest extends TolerantIndexerTestCase
{
    public function testRemovesIncomingReferences(): void
    {
        $indexer = new MemberIndexer();
        $index = $this->createIndex();
        $record1 = $index->get(MemberRecord::fromMemberReference(MemberReference::create(MemberRecord::TYPE_METHOD, 'Foobar', 'bar')));
        $record2 = $index->get(MemberRecord::fromMemberReference(MemberReference::create(MemberRecord::TYPE_METHOD, 'Foobar', 'bar')));

        $this->assertRemovesIncomingReferences($indexer, $index, $record1, $record2);
    }

    /**
     * @dataProvider provideStaticAccess
     * @dataProvider provideInstanceAccess
     */
    public function testMembers(string $manifest, MemberReference $memberReference, int $expectedCount, int $expectedResolvedCount): void
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

        $unconfirmedReferences = [];
        $confirmedReferences = [];
        foreach ($memberRecord->references() as $reference) {
            $fileRecord = $index->get(FileRecord::fromPath($reference));
            assert($fileRecord instanceof FileRecord);
            foreach ($fileRecord->referencesTo($memberRecord) as $positionedReference) {
                $unconfirmedReferences[] = $positionedReference;
            }
            foreach ($fileRecord->referencesTo($memberRecord)->forContainerType($memberReference->containerFqn()) as $positionedReference) {
                $confirmedReferences[] = $positionedReference;
            }
        }

        self::assertCount($expectedCount, $unconfirmedReferences, 'Unconfirmed references');
        self::assertCount($expectedResolvedCount, $confirmedReferences, 'Confirmed references');
    }

    /**
     * @return Generator<mixed>
     */
    public function provideStaticAccess(): Generator
    {
        yield 'single ref' => [
            "// File: src/file1.php\n<?php Foobar::static()",
            MemberReference::create(MemberRecord::TYPE_METHOD, 'Foobar', 'static'),
            1, 1
        ];

        yield 'ambiguous single ref' => [
            "// File: src/file1.php\n<?php Foobar::static(); Barfoo::static();",
            MemberReference::create(MemberRecord::TYPE_METHOD, 'Foobar', 'static'),
            2, 1
        ];

        yield 'multiple ref' => [
            "// File: src/file1.php\n<?php Foobar::static(); Foobar::static();",
            MemberReference::create(MemberRecord::TYPE_METHOD, 'Foobar', 'static'),
            2, 2
        ];

        yield MemberRecord::TYPE_CONSTANT => [
            "// File: src/file1.php\n<?php Foobar::FOOBAR;",
            MemberReference::create(MemberRecord::TYPE_CONSTANT, 'Foobar', 'FOOBAR'),
            1, 1
        ];

        yield 'constant in call' => [
            "// File: src/file1.php\n<?php get(Foobar::FOOBAR);",
            MemberReference::create(MemberRecord::TYPE_CONSTANT, 'Foobar', 'FOOBAR'),
            1, 1
        ];

        yield MemberRecord::TYPE_PROPERTY => [
            "// File: src/file1.php\n<?php Foobar::\$foobar;",
            MemberReference::create(MemberRecord::TYPE_PROPERTY, 'Foobar', '$foobar'),
            1, 1
        ];

        yield 'namespaced static access' => [
            "// File: src/file1.php\n<?php use Barfoo\\Foobar; Foobar::hello();",
            MemberReference::create(MemberRecord::TYPE_METHOD, 'Barfoo\\Foobar', 'hello'),
            1, 1
        ];
    }

    /**
     * @return Generator<mixed>
     */
    public function provideInstanceAccess(): Generator
    {
        yield 'method call' => [
            "// File: src/file1.php\n<?php \$foobar->hello();",
            MemberReference::create(MemberRecord::TYPE_METHOD, 'Foobar', 'hello'),
            1, 0
        ];

        yield 'property access' => [
            "// File: src/file1.php\n<?php \$foobar->hello;",
            MemberReference::create(MemberRecord::TYPE_PROPERTY, 'Foobar', 'hello'),
            1, 0
        ];
    }
}
