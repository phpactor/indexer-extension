<?php

namespace Phpactor\ProjectQuery\Tests\Integration;

use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\ProjectQuery\Adapter\Php\InMemoryIndex;
use Phpactor\ProjectQuery\Adapter\Php\InMemoryRepository;
use Phpactor\ProjectQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\ProjectQuery\Model\Index;
use Phpactor\ProjectQuery\Tests\IntegrationTestCase;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\ReflectorBuilder;
use Symfony\Component\Filesystem\Filesystem;

class InMemoryBuilderIndexTest extends IntegrationTestCase
{
    public function testBuildIndex(): void
    {
        $repository = new InMemoryRepository();
        $index = new InMemoryIndex($repository);
        $indexBuilder = new WorseIndexBuilder(
            $index,
            new SimpleFilesystem($this->workspace()->path('/')),
            ReflectorBuilder::create()->addLocator(
                new StubSourceLocator(
                    ReflectorBuilder::create()->build(),
                    $this->workspace()->path('/'),
                    $this->workspace()->path('/')
                )
            )->build()
        );

        $indexBuilder->refresh();

        self::assertCount(1, $index->query()->implementing(
            FullyQualifiedName::fromString(Index::class)
        ));
    }

    protected function setUp(): void
    {
        $this->workspace()->reset();
        $fs = new Filesystem();
        $fs->mirror(__DIR__ . '/../../lib', $this->workspace()->path('/'));
    }
}
