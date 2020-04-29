<?php

namespace Phpactor\Indexer\Tests\Integration;

use Closure;
use Generator;
use Phpactor\Indexer\Adapter\Php\Serialized\FileRepository;
use Phpactor\Indexer\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Name\FullyQualifiedName;
use function Safe\file_get_contents;

abstract class IndexBuilderIndexTestCase extends InMemoryTestCase
{
    abstract protected function createBuilder(Index $index): IndexBuilder;

    /**
     * @dataProvider provideIndexesClassLike
     */
    public function testIndexesClassLike(string $source, string $name, Closure $assertions): void
    {
        $this->workspace()->loadManifest($source);

        $index = $this->buildIndex();

        $class = $index->query()->class(
            FullyQualifiedName::fromString($name)
        );

        self::assertNotNull($class, 'Class was found');

        $assertions($class);
    }

    /**
     * @return Generator<string, array>
     */
    public function provideIndexesClassLike(): Generator
    {
        yield 'class' => [
            "// File: project/test.php\n<?php class ThisClass {}",
            'ThisClass',
            function (ClassRecord $record) {
                self::assertInstanceOf(ClassRecord::class, $record);
                self::assertEquals($this->workspace()->path('project/test.php'), $record->filePath());
                self::assertEquals('ThisClass', $record->fqn());
                self::assertEquals(6, $record->start()->toInt());
                self::assertEquals('class', $record->type());
            }
        ];

        yield 'namespaced class' => [
            "// File: project/test.php\n<?php namespace Foobar { class ThisClass {} }",
            'Foobar\\ThisClass',
            function (ClassRecord $record) {
                self::assertEquals('Foobar\\ThisClass', $record->fqn());
            }
        ];

        yield 'extended class has implementations' => [
            "// File: project/test.php\n<?php class Foobar {} class Barfoo extends Foobar {}",
            'Foobar',
            function (ClassRecord $record) {
                self::assertCount(1, $record->implementations());
            }
        ];

        yield 'namespaced extended abstract class has implementations' => [
            "// File: project/test.php\n<?php namespace Foobar; abstract class Foobar {} class Barfoo extends Foobar {}",
            'Foobar\Foobar',
            function (ClassRecord $record) {
                self::assertCount(1, $record->implementations());
            }
        ];

        yield 'interface referenced by alias from another namespace' => [
            <<<'EOT'
// File: project/test.php
<?php namespace Foobar; interface Barfoo {} 
// File: project/test2.php
<?php namespace Barfoo;
use Foobar\Barfoo as BarBar;
class Barfoo implements BarBar {}
EOT
            , 'Foobar\Barfoo',
            function (ClassRecord $record) {
                self::assertCount(1, $record->implementations());
            }
        ];

        yield 'class implements' => [
            "// File: project/test.php\n<?php class Foobar {} class Barfoo extends Foobar {}",
            'Barfoo',
            function (ClassRecord $record) {
                self::assertCount(1, $record->implements());
            }
        ];

        yield 'interface has class implementation' => [
            "// File: project/test.php\n<?php interface Foobar {} class ThisClass implements Foobar {}",
            'Foobar',
            function (ClassRecord $record) {
                self::assertCount(1, $record->implementations());
            }
        ];

        yield 'namespaced interface has class implementation' => [
            "// File: project/test.php\n<?php namespace Foobar; interface Foobar {} class ThisClass implements Foobar {}",
            'Foobar\Foobar',
            function (ClassRecord $record) {
                self::assertCount(1, $record->implementations());
            }
        ];

        yield 'interface implements' => [
            "// File: project/test.php\n<?php interface Foobar {} interface Barfoo extends Foobar {}",
            'Foobar',
            function (ClassRecord $record) {
                self::assertCount(1, $record->implementations());
            }
        ];


        yield 'namespaced interface implements' => [
            "// File: project/test.php\n<?php namespace Foobar; interface Foobar {} interface Barfoo extends Foobar {}",
            'Foobar\Foobar',
            function (ClassRecord $record) {
                self::assertCount(1, $record->implementations());
            }
        ];

        yield 'interface has class implementations' => [
            "// File: project/test.php\n<?php interface Foobar {} class ThisClass implements Foobar {} class ThatClass implements Foobar {}",
            'Foobar',
            function (ClassRecord $record) {
                self::assertCount(2, $record->implementations());
            }
        ];

        yield 'interface' => [
            "// File: project/test.php\n<?php interface ThisInterface {}",
            'ThisInterface',
            function (ClassRecord $record) {
                self::assertInstanceOf(ClassRecord::class, $record);
                self::assertEquals($this->workspace()->path('project/test.php'), $record->filePath());
                self::assertEquals('ThisInterface', $record->fqn());
                self::assertEquals(6, $record->start()->toInt());
                self::assertEquals('interface', $record->type());
            }
        ];

        yield 'namespaced interface' => [
            "// File: project/test.php\n<?php namespace Foobar {interface ThisInterface {}}",
            'Foobar\\ThisInterface',
            function (ClassRecord $record) {
                self::assertEquals('Foobar\\ThisInterface', $record->fqn());
            }
        ];

        yield 'trait' => [
            "// File: project/test.php\n<?php trait ThisTrait {}",
            'ThisTrait',
            function (ClassRecord $record) {
                self::assertInstanceOf(ClassRecord::class, $record);
                self::assertEquals($this->workspace()->path('project/test.php'), $record->filePath());
                self::assertEquals('ThisTrait', $record->fqn());
                self::assertEquals(6, $record->start()->toInt());
                self::assertEquals('trait', $record->type());
            }
        ];

        yield 'class uses trait' => [
            <<<'EOT'
// File: project/test1.php
<?php
namespace Foobar;

trait ThisIsTrait {}
// File: project/test2.php
<?php
namespace Barfoo;

use Foobar\ThisIsTrait;

class Hoo
{
    use ThisIsTrait;
}
EOT
            , 'Foobar\ThisIsTrait',
            function (ClassRecord $record) {
                self::assertCount(1, $record->implementations());
            }
        ];
    }

    public function testInterfaceImplementations(): void
    {
        $index = $this->buildIndex();

        $references = $index->query()->implementing(
            FullyQualifiedName::fromString('Index')
        );

        self::assertCount(2, $references);
    }

    public function testFunctions(): void
    {
        $index = $this->buildIndex();

        $function = $index->query()->function(
            FullyQualifiedName::fromString('Hello\world')
        );

        self::assertInstanceOf(Record::class, $function);
    }


    public function testChildClassImplementations(): void
    {
        $index = $this->buildIndex();

        $references = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );

        self::assertCount(2, $references);
    }

    public function testPicksUpNewFiles(): void
    {
        $index = $this->buildIndex();

        $references = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );
        self::assertCount(2, $references);

        $this->workspace()->put(
            'project/Foobar.php',
            <<<'EOT'
<?php

class Foobar extends AbstractClass
{
}
EOT
        );

        $this->buildIndex($index);

        $references = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );

        self::assertCount(3, $references);
    }

    public function testRemovesExistingReferences(): void
    {
        $index = $this->buildIndex();

        $references = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );
        self::assertCount(2, $references);

        $this->workspace()->put(
            'project/AbstractClassImplementation1.php',
            <<<'EOT'
<?php

class AbstractClassImplementation1
{
}
EOT
        );

        $index = $this->buildIndex($index);

        $references = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );

        self::assertCount(1, $references);
    }

    public function testDoesNotRemoveExisting(): void
    {
        $this->workspace()->put(
            'project/0000.php',
            <<<'EOT'
<?php

class Foobar extends AbstractClass
{
}
EOT
        );
        $this->workspace()->put(
            'project/ZZZZ.php',
            <<<'EOT'
<?php

class ZedFoobar extends AbstractClass
{
}
EOT
        );

        $index = $this->buildIndex();

        $references = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );
        self::assertCount(4, $references);

        $this->workspace()->put(
            'project/0000.php',
            <<<'EOT'
<?php

class Foobar
{
}
EOT
        );

        $index = $this->buildIndex($index);

        $references = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );

        self::assertCount(3, $references);
    }

    public function testIndexesSymlinkedFiles(): void
    {
        $this->workspace()->loadManifest(
            <<<'EOT'
// File: other-project/One.php
<?php class Foobar()
{
}
EOT
        );

        symlink($this->workspace()->path('other-project'), $this->workspace()->path('project/other-project'));

        $index = $this->buildIndex();

        $class = $index->query()->class(
            FullyQualifiedName::fromString('Foobar')
        );

        self::assertInstanceOf(ClassRecord::class, $class, 'Class was found');
        self::assertEquals($this->workspace()->path('/project/other-project/One.php'), $class->filePath());
    }


    protected function setUp(): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest(file_get_contents(__DIR__ . '/Manifest/buildIndex.php.test'));
    }

    private function buildIndex(?Index $index = null): Index
    {
        if (null === $index) {
            $repository = new FileRepository($this->workspace()->path('index'));
            $index = new SerializedIndex($repository);
        }

        $provider = $this->fileListProvider();
        $indexer = new Indexer($this->createBuilder($index), $index, $provider);
        $indexer->getJob()->run();

        return $index;
    }
}
