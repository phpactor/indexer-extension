<?php

namespace Phpactor\ProjectQuery\Tests\Integration\Worse;

use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\ProjectQuery\Adapter\Php\InMemory\InMemoryIndex;
use Phpactor\ProjectQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\ProjectQuery\Tests\Integration\IndexBuilderIndexTestCase;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\ReflectorBuilder;
use Psr\Log\NullLogger;

class WorseIndexMemoryBuilderTest extends IndexBuilderIndexTestCase
{
    protected function createBuilder(InMemoryIndex $index): WorseIndexBuilder
    {
        $indexBuilder = new WorseIndexBuilder(
            $index,
            new SimpleFilesystem($this->workspace()->path('/project')),
            ReflectorBuilder::create()->addLocator(
                new StubSourceLocator(
                    ReflectorBuilder::create()->build(),
                    $this->workspace()->path('/'),
                    $this->workspace()->path('/')
                )
            )->build(),
            new NullLogger()
        );
        return $indexBuilder;
    }
}
