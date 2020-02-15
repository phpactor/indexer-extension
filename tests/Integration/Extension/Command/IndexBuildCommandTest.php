<?php

namespace Phpactor\WorkspaceQuery\Tests\Integration\Extension\Command;

use PHPUnit\Framework\TestCase;
use Phpactor\WorkspaceQuery\Tests\IntegrationTestCase;
use Symfony\Component\Process\Process;

class IndexBuildCommandTest extends IntegrationTestCase
{
    public function testRefreshIndex(): void
    {
        $this->initProject();

        $process = new Process([
            __DIR__ . '/../../../../bin/query',
            'index:build',
        ], $this->workspace()->path());
        $process->mustRun();
        self::assertEquals(0, $process->getExitCode());
    }
}
