<?php

namespace Phpactor\WorkspaceQuery\Tests\Benchmark;

use Phpactor\WorkspaceQuery\Extension\WorkspaceQueryExtension;
use Phpactor\WorkspaceQuery\Tests\Integration\Extension\WorkspaceQueryExtensionTest;

/**
 * @BeforeMethods({"init"})
 * @OutputTimeUnit("seconds")
 * @Iterations(10)
 */
class WorseIndexBuilderBench extends WorkspaceQueryExtensionTest
{
    public function init()
    {
        $this->setUp();
    }

    public function benchIndex(): void
    {
        $this->testBuildIndex();
    }
}
