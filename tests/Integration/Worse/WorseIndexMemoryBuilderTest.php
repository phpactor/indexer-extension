<?php

namespace Phpactor\WorkspaceQuery\Tests\Integration\Worse;

use Phpactor\WorkspaceQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\WorkspaceQuery\Model\Index;
use Phpactor\WorkspaceQuery\Model\IndexBuilder;
use Phpactor\WorkspaceQuery\Tests\Integration\IndexBuilderIndexTestCase;
use Psr\Log\NullLogger;

class WorseIndexMemoryBuilderTest extends IndexBuilderIndexTestCase
{
    protected function createBuilder(Index $index): IndexBuilder
    {
        $indexBuilder = new WorseIndexBuilder(
            $index,
            $this->createReflector(),
            new NullLogger()
        );
        return $indexBuilder;
    }
}
