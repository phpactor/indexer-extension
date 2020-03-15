<?php

namespace Phpactor\Indexer\Extension\Rpc;

use Phpactor\Extension\Rpc\Handler;
use Phpactor\Extension\Rpc\Response;
use Phpactor\Extension\Rpc\Response\EchoResponse;
use Phpactor\MapResolver\Resolver;
use Phpactor\Indexer\Model\Indexer;

class IndexHandler implements Handler
{
    const NAME = 'index';
    const PARAM_WATCH = 'watch';
    const PARAM_INTERVAL = 'interval';


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
        $resolver->setDefaults([
            self::PARAM_WATCH => false,
            self::PARAM_INTERVAL => 5
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
        while (true) {
            $job = $this->indexer->getJob();
            $job->run();

            if ($arguments[self::PARAM_WATCH] === false) {
                break;
            }

            sleep($arguments[self::PARAM_INTERVAL]);
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
