<?php

namespace Phpactor\WorkspaceQuery\Extension\LanguageServer;

use Amp\Deferred;
use Amp\Delayed;
use Amp\Promise;
use Amp\Success;
use Generator;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionList;
use LanguageServerProtocol\CompletionOptions;
use LanguageServerProtocol\MessageType;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\SignatureHelpOptions;
use LanguageServerProtocol\TextDocumentItem;
use LanguageServerProtocol\TextEdit;
use LanguageServerProtocol\WorkDoneProgressBegin;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\TypedCompletorRegistry;
use Phpactor\Extension\LanguageServerCompletion\Util\PhpactorToLspCompletionType;
use Phpactor\Extension\LanguageServer\Helper\OffsetHelper;
use Phpactor\LanguageServer\Core\Handler\CanRegisterCapabilities;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Handler\ServiceProvider;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Server\Transmitter\MessageTransmitter;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;
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

    public function methods(): array
    {
        return [
        ];
    }

    /**
     * {@inheritDoc}
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

    private function showMessage(MessageTransmitter $transmitter, string $message)
    {
        $transmitter->transmit(new NotificationMessage('window/showMessage', [
            'type' => MessageType::INFO,
            'message' => $message
        ]));
    }
}
