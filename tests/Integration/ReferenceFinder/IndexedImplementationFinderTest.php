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

class IndexedImplementationFinderTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        $this->workspace()->reset();
    }

    /**
     * @dataProvider provideClassLikes
     * @dataProvider provideClassMembers
     */
    public function testFinder(string $manifest, int $expectedLocationCount): void
    {
        $this->workspace()->loadManifest($manifest);
        [ $source, $offset ] = ExtractOffset::fromSource($this->workspace()->getContents('project/subject.php'));
        $this->workspace()->put('project/subject.php', $source);

        $index = $this->buildIndex();

        $implementationFinder = new IndexedReferenceFinder(
            $index,
            $this->createReflector()
        );

        $locations = $implementationFinder->find(
            TextDocumentBuilder::create($source)->build(),
            ByteOffset::fromInt((int)$offset)
        );

        self::assertCount($expectedLocationCount, $locations);
    }

    /**
     * @return Generator<mixed>
     */
    public function provideClassLikes(): Generator
    {
        yield 'interface implementations' => [
            <<<'EOT'
// File: project/subject.php
<?php interface Fo<>oInterface {}
// File: project/class.php
<?php

class Foobar implements FooInterface {}
class Barfoo implements FooInterface {}
EOT
        ,
            2
        ];

        yield 'class implementations' => [
            <<<'EOT'
// File: project/subject.php
<?php class Fo<>o {}
// File: project/class.php
<?php

class Foobar extends Foo {}
class Barfoo extends Foo {}
EOT
        ,
            2
        ];

        yield 'abstract class implementations' => [
            <<<'EOT'
// File: project/subject.php
<?php abstract class Fo<>o {}
// File: project/class.php
<?php

class Foobar extends Foo {}
class Barfoo extends Foo {}
EOT
        ,
            2
        ];
    }

    /**
     * @return Generator<mixed>
     */
    public function provideClassMembers(): Generator
    {
        yield 'none' => [
            <<<'EOT'
// File: project/subject.php
<?php interface FooInterface {
   public function doT<>his();
}
EOT
        ,
            0
        ];

        yield 'interface member' => [
            <<<'EOT'
// File: project/subject.php
<?php interface FooInterface {
   public function doT<>his();
}
// File: project/class.php
<?php

class Foobar implements FooInterface {
    public function doThis();
}
class Barfoo implements FooInterface {
    public function doThis();
}
EOT
        ,
            2
        ];

        yield 'class member' => [
            <<<'EOT'
// File: project/subject.php
<?php class Foo {
   public function doT<>his();
}
// File: project/class.php
<?php

class Foobar extends Foo {
    public function doThis();
}
EOT
        ,
            1
        ];
    }
}
