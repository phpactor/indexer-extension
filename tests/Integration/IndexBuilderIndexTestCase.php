<?php

namespace Phpactor\Indexer\Tests\Integration;

use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Adapter\Php\InMemory\InMemoryIndex;
use Phpactor\Indexer\Adapter\Php\InMemory\InMemoryRepository;
use function Safe\file_get_contents;

abstract class IndexBuilderIndexTestCase extends InMemoryTestCase
{
    abstract protected function createBuilder(Index $index): IndexBuilder;

    public function testInterfaceImplementations(): void
    {
        $index = $this->buildIndex();

        $references = $foo = $index->query()->implementing(
            FullyQualifiedName::fromString('Index')
        );

        self::assertCount(2, $references);
    }

    public function testFunctions(): void
    {
        $index = $this->buildIndex();

        $function = $foo = $index->query()->function(
            FullyQualifiedName::fromString('Hello\world')
        );

        self::assertInstanceOf(Record::class, $function);
    }


    public function testChildClassImplementations(): void
    {
        $index = $this->buildIndex();

        $references = $foo = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );

        self::assertCount(2, $references);
    }

    public function testPicksUpNewFiles(): void
    {
        $repository = new InMemoryRepository();
        $index = new InMemoryIndex($repository);
        $indexBuilder = $this->createBuilder($index);
        $provider = $this->fileListProvider();
        $indexer = new Indexer($indexBuilder, $index, $provider);
        $indexer->getJob()->run();

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

        $provider = $this->fileListProvider();
        $indexer = new Indexer($indexBuilder, $index, $provider);
        $indexer->getJob()->run();
        $references = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );

        self::assertCount(3, $references);
    }

    public function testRemovesExistingReferences(): void
    {
        $repository = new InMemoryRepository();
        $index = new InMemoryIndex($repository);

        $indexBuilder = $this->createBuilder($index);
        $fileList = $this->fileListProvider();
        $indexer = new Indexer($indexBuilder, $index, $fileList);
        $indexer->getJob()->run();

        $references = $foo = $index->query()->implementing(
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

        $indexBuilder = $this->createBuilder($index);
        $fileList = $this->fileListProvider();
        $indexer = new Indexer($indexBuilder, $index, $fileList);
        $indexer->getJob()->run();
        $references = $foo = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );
        self::assertCount(1, $references);
    }

    protected function setUp(): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest(file_get_contents(__DIR__ . '/Manifest/buildIndex.php.test'));
    }

    private function buildIndex(): InMemoryIndex
    {
        $repository = new InMemoryRepository();
        $index = new InMemoryIndex($repository);
        $provider = $this->fileListProvider();
        $indexer = new Indexer($this->createBuilder($index), $index, $provider);
        $indexer->getJob()->run();
        return $index;
    }
}
