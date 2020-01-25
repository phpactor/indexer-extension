<?php

namespace Phpactor\ProjectQuery\Tests\Integration\Worse;

use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\ProjectQuery\Adapter\Php\InMemoryIndex;
use Phpactor\ProjectQuery\Adapter\Php\InMemoryRepository;
use Phpactor\ProjectQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\ProjectQuery\Model\Index;
use Phpactor\ProjectQuery\Tests\IntegrationTestCase;
use Phpactor\ProjectQuery\Tests\Integration\AbstractIndexBuilderIndexTestCase;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\ReflectorBuilder;
use Symfony\Component\Filesystem\Filesystem;

class WorseIndexMemoryBuilderTest extends AbstractIndexBuilderIndexTestCase
{
    public function createBuilder(InMemoryIndex $index): WorseIndexBuilder
    {
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
        return $indexBuilder;
    }
}
