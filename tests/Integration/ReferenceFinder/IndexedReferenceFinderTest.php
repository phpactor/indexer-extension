<?php

namespace Phpactor\Indexer\Tests\Integration\ReferenceFinder;

use Generator;
use Phpactor\Indexer\Adapter\ReferenceFinder\IndexedReferenceFinder;
use Phpactor\Indexer\Tests\IntegrationTestCase;
use Phpactor\ReferenceFinder\PotentialLocation;
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
     * @dataProvider provideMembers
     * @dataProvider provideUnknown
     */
    public function testFinder(string $manifest, int $expectedLocationCount): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest($manifest);
        [ $source, $offset ] = ExtractOffset::fromSource($this->workspace()->getContents('project/subject.php'));
        $this->workspace()->put('project/subject.php', $source);

        $index = $this->buildIndex();

        $referenceFinder = new IndexedReferenceFinder(
            $this->indexQuery($index),
            $this->createReflector(),
        );

        $locations = $referenceFinder->findReferences(
            TextDocumentBuilder::create($source)->build(),
            ByteOffset::fromInt((int)$offset)
        );

        $locations = array_filter(iterator_to_array($locations), function (PotentialLocation $location) {
            return $location->isSurely();
        });

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
<?php new Fo<>o();
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
            2
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
<?php function he<>llo_world() {}
// File: project/class1.php
<?php

hello_world();
// File: project/class2.php
<?php

hello_world();
EOT
        ,
            2
        ];
    }

    /**
     * @return Generator<mixed>
     */
    public function provideMembers(): Generator
    {
        yield 'static members' => [
            <<<'EOT'
// File: project/subject.php
<?php Foobar::b<>ar() {}
// File: project/class1.php
<?php Foobar::bar() {}
// File: project/class2.php
<?php
<?php Foobar::bar() {}
EOT
        ,
            3
        ];

        yield 'namespaced static members' => [
            <<<'EOT'
// File: project/subject.php
<?php namespace Bar; Foobar::b<>ar() {}
// File: project/class1.php
<?php Bar\Foobar::bar() {}
// File: project/class2.php
<?php
<?php use Bar\Foobar; Foobar::bar() {}
EOT
        ,
            3
        ];

        yield 'instance members' => [
            <<<'EOT'
// File: project/subject.php
<?php namespace Bar; $foo = new Foobar(); $foo->b<>ar();

// File: project/class1.php
<?php namespace Bar; class Foobar { public function bar() {}}

EOT
        ,
            1
        ];
    }

    /**
     * @return Generator<mixed>
     */
    public function provideUnknown(): Generator
    {
        yield 'variable' => [
            <<<'EOT'
// File: project/subject.php
<?php $a<>sd;
EOT
        ,
            0
        ];
    }
}
