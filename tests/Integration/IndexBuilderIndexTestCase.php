<?php

namespace Phpactor\WorkspaceQuery\Tests\Integration;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\WorkspaceQuery\Adapter\Php\InMemory\InMemoryIndex;
use Phpactor\WorkspaceQuery\Adapter\Php\InMemory\InMemoryRepository;
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
        $indexBuilder->build();

        $references = $foo = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );
        self::assertCount(2, $references);

        $this->workspace()->put('project/Foobar.php', <<<'EOT'
<?php

class Foobar extends AbstractClass
{
}
EOT
    );

		$indexBuilder->build();
        $references = $foo = $index->query()->implementing(
            FullyQualifiedName::fromString('AbstractClass')
        );
        self::assertCount(3, $references);
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
        $this->createBuilder($index)->build();
        return $index;
    }
}
