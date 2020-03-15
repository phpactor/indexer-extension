<?php

namespace Phpactor\Indexer\Tests\Integration\Amp\Watcher;

use Phpactor\Indexer\Tests\IntegrationTestCase;
use Phpactor\Indexer\Adapter\Amp\FileModification;
use Phpactor\Indexer\Adapter\Amp\Watcher\InotifyWatcher;
use Psr\Log\NullLogger;

class InotifyWatcherTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        $this->workspace()->reset();
    }

    public function testWatch(): void
    {
        $watcher = new InotifyWatcher($this->workspace()->path(), new NullLogger());
        $promise = \Amp\call(function () use ($watcher) {
            return yield $watcher->wait();
        });

        // ensyure that the watcher is started by the time we put the file in
        // place
        usleep(5000);

        $this->workspace()->put('foo', 'bar');

        $result = \Amp\Promise\wait($promise);
        $this->assertEquals(
            new FileModification($this->workspace()->path() . '/', 'CREATE', 'foo'),
            $result
        );
    }
}
