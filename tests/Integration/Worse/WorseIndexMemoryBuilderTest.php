<?php

namespace Phpactor\ProjectQuery\Tests\Integration\Worse;

use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\ProjectQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\ProjectQuery\Model\Index;
use Phpactor\ProjectQuery\Model\IndexBuilder;
use Phpactor\ProjectQuery\Tests\Integration\IndexBuilderIndexTestCase;
use Psr\Log\NullLogger;

class WorseIndexMemoryBuilderTest extends IndexBuilderIndexTestCase
{
    protected function createBuilder(Index $index): IndexBuilder
    {
        $indexBuilder = new WorseIndexBuilder(
            $index,
            new SimpleFilesystem($this->workspace()->path('/project')),
            $this->createReflector(),
            new NullLogger()
        );
        return $indexBuilder;
    }
}
