<?php

namespace Phpactor\Indexer\Tests\Integration\Tolerant\Indexer;

use Generator;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\ClassDeclarationIndexer;
use Phpactor\Indexer\Tests\Integration\Tolerant\TolerantIndexerTestCase;

class ClassDeclarationIndexerTest extends TolerantIndexerTestCase
{
    /**
     * @dataProvider provideImplementations
     */
    public function testImplementations(string $manifest, string $fqn, int $expectedCount): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest($manifest);
        $index = $this->createIndex();
        $this->runIndexer(new ClassDeclarationIndexer(), $index, 'src');

        self::assertCount($expectedCount, $this->indexQuery($index)->class()->implementing($fqn));
    }

    /**
     * @return Generator<mixed>
     */
    public function provideImplementations(): Generator
    {
        yield 'no implementations' => [
            "// File: src/file1.php\n<?php class Barfoo {}",
            'Foobar',
            0
        ];

        yield 'class implements' => [
            "// File: src/file1.php\n<?php class Barfoo implements Foobar{}",
            'Foobar',
            1
        ];

        yield 'class implements multiple' => [
            "// File: src/file1.php\n<?php class Barfoo implements Baz, Foobar{}",
            'Foobar',
            1
        ];

        yield 'abstract class implements' => [
            "// File: src/file1.php\n<?php abstract class Barfoo implements Foobar{}",
            'Foobar',
            1
        ];
    }
}
