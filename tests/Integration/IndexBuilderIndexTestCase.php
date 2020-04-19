<?php

namespace Phpactor\Indexer\Tests\Integration;

use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Adapter\Php\InMemory\InMemoryIndex;
use Phpactor\Indexer\Adapter\Php\InMemory\InMemoryRepository;
use function Safe\file_get_contents;

abstract class IndexBuilderIndexTestCase extends InMemoryTestCase
{
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

        self::assertInstanceOf(FunctionRecord::class, $function);
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
        $fileList = $this->fileList($index);
        $indexBuilder->build($fileList);

        $references = $foo = $index->query()->implementing(
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

        $fileList = $this->fileList($index);
        $indexBuilder->build($fileList);
        $references = $foo = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );
        self::assertCount(3, $references);
    }

    public function testRemovesExistingReferences(): void
    {
        $repository = new InMemoryRepository();
        $index = new InMemoryIndex($repository);
        $fileList = $this->fileList($index);
        $indexBuilder = $this->createBuilder($index);
        $indexBuilder->build($fileList);

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

        $fileList = $this->fileList($index);
        $indexBuilder->build($fileList);
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
        $fileList = $this->fileList($index);
        $this->createBuilder($index)->build($fileList);
        return $index;
    }
}
