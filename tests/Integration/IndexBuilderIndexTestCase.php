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
use Phpactor\Indexer\Adapter\Php\InMemory\InMemoryIndex;
use Phpactor\Indexer\Adapter\Php\InMemory\InMemoryRepository;
use function Safe\file_get_contents;

abstract class IndexBuilderIndexTestCase extends InMemoryTestCase
{
    abstract protected function createBuilder(Index $index): IndexBuilder;

    /**
     * @dataProvider provideIndexesClassLike
     */
    public function testIndexesClassLike(string $source, string $name, Closure $assertions): void
    {
        $this->workspace()->put('project/test.php', $source);

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
            '<?php class ThisClass {}',
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
            '<?php namespace Foobar { class ThisClass {} }',
            'Foobar\\ThisClass',
            function (ClassRecord $record) {
                self::assertEquals('Foobar\\ThisClass', $record->fqn());
            }
        ];

        yield 'interface has class implementation' => [
            '<?php interface Foobar {} class ThisClass implements Foobar {}',
            'Foobar',
            function (ClassRecord $record) {
                self::assertCount(1, $record->implementations());
            }
        ];

        yield 'interface has class implementations' => [
            '<?php interface Foobar {} class ThisClass implements Foobar {} class ThatClass implements Foobar {}',
            'Foobar',
            function (ClassRecord $record) {
                self::assertCount(2, $record->implementations());
            }
        ];

        yield 'interface' => [
            '<?php interface ThisInterface {}',
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
            '<?php namespace Foobar {interface ThisInterface {}}',
            'Foobar\\ThisInterface',
            function (ClassRecord $record) {
                self::assertEquals('Foobar\\ThisInterface', $record->fqn());
            }
        ];

        yield 'trait' => [
            '<?php trait ThisTrait {}',
            'ThisTrait',
            function (ClassRecord $record) {
                self::assertInstanceOf(ClassRecord::class, $record);
                self::assertEquals($this->workspace()->path('project/test.php'), $record->filePath());
                self::assertEquals('ThisTrait', $record->fqn());
                self::assertEquals(6, $record->start()->toInt());
                self::assertEquals('trait', $record->type());
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
