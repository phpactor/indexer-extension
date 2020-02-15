<?php

namespace Phpactor\WorkspaceQuery\Tests\Integration\ReferenceFinder;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\WorkspaceQuery\Adapter\ReferenceFinder\IndexedImplementationFinder;
use Phpactor\WorkspaceQuery\Tests\Integration\InMemoryTestCase;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;
use RuntimeException;

class IndexedImplementationFinderTest extends InMemoryTestCase
{
    protected function setUp(): void
    {
        $this->initProject();
    }

    public function testFinder()
    {
        $index = $this->createIndex();
        $builder = $this->createBuilder($index);
        $fileList = $this->fileList($index);
        $builder->build($fileList);

        $implementationFinder = new IndexedImplementationFinder(
            $index,
            $this->createReflector()
        );
        $locations = $implementationFinder->findImplementations(TextDocumentBuilder::create(
            <<<'EOT'
<?php

new Index();
EOT
        )->build($fileList), ByteOffset::fromInt(8));

        self::assertCount(2, $locations);
    }
}
