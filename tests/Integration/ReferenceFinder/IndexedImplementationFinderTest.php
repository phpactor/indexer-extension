<?php

namespace Phpactor\ProjectQuery\Tests\Integration\ReferenceFinder;

use Phpactor\ProjectQuery\Adapter\ReferenceFinder\IndexedImplementationFinder;
use Phpactor\ProjectQuery\Tests\Integration\InMemoryTestCase;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;
use RuntimeException;

class IndexedImplementationFinderTest extends InMemoryTestCase
{
    protected function setUp(): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest(file_get_contents(__DIR__ . '/../Manifest/buildIndex.php.test'));
    }

    public function testFinder()
    {
        $index = $this->createIndex();
        $generator = $this->createBuilder($index)->build();
        iterator_to_array($generator);

        $implementationFinder = new IndexedImplementationFinder($index, $this->createReflector());
        $locations = $implementationFinder->findImplementations(TextDocumentBuilder::create(
            <<<'EOT'
<?php

new Index();
EOT
        )->build(), ByteOffset::fromInt(8));

        self::assertCount(2, $locations);
    }

    public function testThrowsExceptionIfIndexNotInitialized()
    {
        $this->expectException(RuntimeException::class);
        $index = $this->createIndex();
        $index->reset();
        $implementationFinder = new IndexedImplementationFinder(
            $index,
            $this->createReflector()
        );
        $locations = $implementationFinder->findImplementations(TextDocumentBuilder::create(
            <<<'EOT'
<?php

new Index();
EOT
        )->build(), ByteOffset::fromInt(8));
    }
}
