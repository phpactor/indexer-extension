<?php

namespace Phpactor\Indexer\Extension\Rpc;

use Amp\Delayed;
use Amp\Loop;
use Phpactor\AmpFsWatch\ModifiedFile;
use Phpactor\AmpFsWatch\WatcherProcess;
use Phpactor\Extension\Rpc\Handler;
use Phpactor\Extension\Rpc\Response;
use Phpactor\Extension\Rpc\Response\EchoResponse;
use Phpactor\MapResolver\Resolver;
use Phpactor\Indexer\Model\Indexer;
use phpDocumentor\Reflection\DocBlock\Description;

class IndexHandler implements Handler
{
    const NAME = 'index';
    const PARAM_WATCH = 'watch';
    const PARAM_INTERVAL = 'interval';

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var WatcherProcess
     */
    private $watcher;

    public function __construct(Indexer $indexer, WatcherProcess $watcher)
    {
        $this->indexer = $indexer;
        $this->watcher = $watcher;
    }

    public function configure(Resolver $resolver): void
    {
        $resolver->setDefaults([
            self::PARAM_WATCH => false,
            self::PARAM_INTERVAL => 5000
        ]);
        $resolver->setTypes([
            self::PARAM_INTERVAL => 'integer'
        ]);
    }

    /**
     * @param array<string,mixed> $arguments
     */
    public function handle(array $arguments): Response
    {
        $job = $this->indexer->getJob();
        $job->run();

        if ($arguments[self::PARAM_WATCH] === true) {
            Loop::run(function () use ($arguments) {
                while (null !== $file = $this->watcher->wait()) {
                    assert($file instanceof ModifiedFile);
                    $job = $this->indexer->getJob($file->path());
                    $job->run();
                    yield new Delayed($arguments[self::PARAM_INTERVAL]);
                }
            });
        }

        return EchoResponse::fromMessage(sprintf(
            'Indexed %s files',
            $job->size()
        ));
    }

    public function name(): string
    {
        return self::NAME;
    }
}
