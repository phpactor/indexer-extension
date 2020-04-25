<?php

namespace Phpactor\Indexer\Tests\Integration\ReferenceFinder;

use Phpactor\Indexer\Adapter\ReferenceFinder\IndexedImplementationFinder;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Tests\Integration\InMemoryTestCase;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;

class IndexedImplementationFinderTest extends InMemoryTestCase
{
    protected function setUp(): void
    {
        $this->initProject();
    }

    public function testFinder(): void
    {
        $index = $this->createInMemoryIndex();
        $indexBuilder = $this->createTestBuilder($index);
        $fileList = $this->fileListProvider();
        $indexer = new Indexer($indexBuilder, $index, $fileList);
        $indexer->getJob()->run();

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
