<?php

namespace Phpactor\WorkspaceQuery\Tests\Integration\Amp\Watcher;

use PHPUnit\Framework\TestCase;
use Phpactor\TestUtils\Workspace;
use Phpactor\WorkspaceQuery\Adapter\Amp\FileModification;
use Phpactor\WorkspaceQuery\Adapter\Amp\Watcher\InotifyWatcher;
use Psr\Log\NullLogger;

class InotifyWatcherTest extends TestCase
{
    /**
     * @var Workspace
     */
    private $workspace;

    protected function setUp(): void
    {
        $this->workspace = new Workspace(__DIR__ . '/../Workspace');
        $this->workspace->reset();
    }

    public function testWatch(): void
    {
        $watcher = new InotifyWatcher($this->workspace->path(), new NullLogger());
        $promise = \Amp\call(function () use ($watcher) {
            return yield $watcher->wait();
        });

        // ensyure that the watcher is started by the time we put the file in
        // place
        usleep(5000);

        $this->workspace->put('foo', 'bar');

        $result = \Amp\Promise\wait($promise);
        $this->assertEquals(
            new FileModification($this->workspace->path() . '/', 'CREATE', 'foo'),
            $result
        );
    }
}
