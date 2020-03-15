<?php

namespace Phpactor\WorkspaceQuery\Extension\LanguageServer;

use Amp\Delayed;
use Amp\Promise;
use Amp\Success;
use LanguageServerProtocol\MessageType;
use Phpactor\LanguageServer\Core\Handler\ServiceProvider;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Server\Transmitter\MessageTransmitter;
use Phpactor\WorkspaceQuery\Adapter\Amp\FileModification;
use Phpactor\WorkspaceQuery\Adapter\Amp\Watcher;
use Phpactor\WorkspaceQuery\Model\Indexer;

class IndexerHandler implements ServiceProvider
{
    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var Watcher
     */
    private $watcher;

    public function __construct(
        Indexer $indexer,
        Watcher $watcher
    ) {
        $this->indexer = $indexer;
        $this->watcher = $watcher;
    }

    /**
     * @return array<string>
     */
    public function methods(): array
    {
        return [
        ];
    }

    /**
     * @return array<string>
     */
    public function services(): array
    {
        return [
            'indexerService'
        ];
    }

    public function indexerService(MessageTransmitter $transmitter): Promise
    {
        return \Amp\call(function () use ($transmitter) {
            $job = $this->indexer->getJob();
            $this->showMessage($transmitter, sprintf('Indexing "%s" PHP files', $job->size()));

            $index = 0;
            foreach ($job->generator() as $file) {
                yield new Delayed(1);
            }

            $this->showMessage($transmitter, 'Index initialized, watching.');

            while ($modifiedFile = yield $this->watcher->wait()) {
                assert($modifiedFile instanceof FileModification);

                $job = $this->indexer->getJob(rtrim(
                    $modifiedFile->watchedFilename(),
                    '/'
                ) .'/'. $modifiedFile->eventFilename());

                foreach ($job->generator() as $file) {
                    yield new Delayed(1);
                }
            }

            return new Success();
        });
    }

    private function showMessage(MessageTransmitter $transmitter, string $message): void
    {
        $transmitter->transmit(new NotificationMessage('window/showMessage', [
            'type' => MessageType::INFO,
            'message' => $message
        ]));
    }
}
