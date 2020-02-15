<?php

namespace Phpactor\WorkspaceQuery\Tests\Integration;

use Phpactor\Name\FullyQualifiedName;
use function Safe\file_get_contents;

abstract class IndexTestCase extends InMemoryTestCase
{
    public function testBuild(): void
    {
        $index = $this->createIndex();
        $builder = $this->createBuilder($index);
        $builder->build($this->fileList($index));
        $references = $foo = $index->query()->implementing(
            FullyQualifiedName::fromString('Index')
        );

        self::assertCount(2, $references);
    }

    protected function setUp(): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest(file_get_contents(__DIR__ . '/Manifest/buildIndex.php.test'));
    }
}
