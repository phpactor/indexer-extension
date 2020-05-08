<?php

namespace Phpactor\Indexer\Tests\Integration\ReferenceFinder;

use Generator;
use Phpactor\Indexer\Adapter\ReferenceFinder\IndexedImplementationFinder;
use Phpactor\Indexer\Integration\ReferenceFinder\IndexedReferenceFinder;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Tests\IntegrationTestCase;
use Phpactor\TestUtils\ExtractOffset;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;

class IndexedReferenceFinderTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        $this->workspace()->reset();
    }

    /**
     * @dataProvider provideClasses
     * @dataProvider provideFunctions
     */
    public function testFinder(string $manifest, int $expectedLocationCount): void
    {
        $this->workspace()->loadManifest($manifest);
        [ $source, $offset ] = ExtractOffset::fromSource($this->workspace()->getContents('project/subject.php'));
        $this->workspace()->put('project/subject.php', $source);

        $index = $this->buildIndex();

        $referenceFinder = new IndexedReferenceFinder(
            $index,
            $this->createReflector()
        );

        $locations = $referenceFinder->findReferences(
            TextDocumentBuilder::create($source)->build(),
            ByteOffset::fromInt((int)$offset)
        );

        self::assertCount($expectedLocationCount, $locations);
    }

    /**
     * @return Generator<mixed>
     */
    public function provideClasses(): Generator
    {
        yield 'single class' => [
            <<<'EOT'
// File: project/subject.php
<?php class Fo<>o {}
EOT
        ,
            1
        ];

        yield 'class references' => [
            <<<'EOT'
// File: project/subject.php
<?php class Fo<>o {}
// File: project/class1.php
<?php

new Foo();
// File: project/class2.php
<?php

Foo::bar();
EOT
        ,
            3
        ];
    }

    /**
     * @return Generator<mixed>
     */
    public function provideFunctions(): Generator
    {
        yield 'function references' => [
            <<<'EOT'
// File: project/subject.php
<?php fuction he<>llo_world() {}
// File: project/class1.php
<?php

hello_world();
// File: project/class2.php
<?php

hello_world();
EOT
        ,
            3
        ];
    }
}
