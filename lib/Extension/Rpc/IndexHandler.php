<?php

namespace Phpactor\WorkspaceQuery\Extension\Rpc;

use Phpactor\Extension\Rpc\Handler;
use Phpactor\Extension\Rpc\Response;
use Phpactor\Extension\Rpc\Response\EchoResponse;
use Phpactor\MapResolver\Resolver;
use Phpactor\WorkspaceQuery\Model\Indexer;

class IndexHandler implements Handler
{
    const NAME = 'index';

    /**
     * @var Indexer
     */
    private $indexer;

    public function __construct(Indexer $indexer)
    {
        $this->indexer = $indexer;
    }

    public function configure(Resolver $resolver): void
    {
    }

    /**
     * @param array<string,mixed> $arguments
     */
    public function handle(array $arguments): Response
    {
        $job = $this->indexer->getJob();
        $job->run();

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
