<?php

namespace Phpactor\Indexer\Tests\Unit\Extension\LanguageServer;

use PHPUnit\Framework\TestCase;
use Phpactor\AmpFsWatch\ModifiedFile;
use Phpactor\AmpFsWatch\ModifiedFileQueue;
use Phpactor\AmpFsWatch\Watcher\TestWatcher\TestWatcher;
use Phpactor\Indexer\Extension\LanguageServer\IndexerHandler;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\Indexer\Tests\IntegrationTestCase;
use Phpactor\LanguageServer\Core\Server\Transmitter\NullMessageTransmitter;
use Phpactor\LanguageServer\Test\HandlerTester;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class IndexerHandlerTest extends IntegrationTestCase
{
    /**
     * @var ObjectProphecy|LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    public function testIndexer(): void
    {
        $this->workspace()->put('Foobar.php', <<<'EOT'
<?php
EOT
        );
        \Amp\Promise\wait(\Amp\call(function () {
            $indexer = $this->container()->get(Indexer::class);
            $watcher = new TestWatcher(new ModifiedFileQueue([
                new ModifiedFile($this->workspace()->path('Foobar.php'), ModifiedFile::TYPE_FILE),
            ]));
            $handler = new IndexerHandler($indexer, $watcher, $this->logger->reveal());
            yield $handler->indexerService(new NullMessageTransmitter());
        }));

        $this->logger->debug(sprintf(
            'Indexed file: %s',
            $this->workspace()->path('Foobar.php')
        ))->shouldHaveBeenCalled();
    }
}
