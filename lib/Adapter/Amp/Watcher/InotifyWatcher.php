<?php

namespace Phpactor\WorkspaceQuery\Adapter\Amp\Watcher;

use Amp\Process\Process;
use Amp\Promise;
use Phpactor\WorkspaceQuery\Adapter\Amp\FileModification;
use Phpactor\WorkspaceQuery\Adapter\Amp\Watcher;
use Psr\Log\LoggerInterface;
use RuntimeException;

class InotifyWatcher implements Watcher
{
    /**
     * @var bool
     */
    private $processStarted = false;

    /**
     * @var Process
     */
    private $process;

    /**
     * @var string
     */
    private $buffer;

    /**
     * @var string
     */
    private $path;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(string $path, LoggerInterface $logger)
    {
        $this->path = $path;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function wait(): Promise
    {
        return \Amp\call(function () {
            if (!$this->processStarted) {
                $this->process = yield $this->startProcess();
                $this->processStarted = true;
            }

            return \Amp\call(function () {
                $buffer = '';
                $stdout = $this->process->getStdout();
                while (null !== $chunk = yield $stdout->read()) {
                    foreach (str_split($chunk) as $char) {
                        $this->buffer .= $char;

                        if ($char !== "\n") {
                            continue;
                        }

                        $read = $this->buffer;
                        $this->buffer = '';
                        $modification = FileModification::fromCsvString($read);
                        $this->logger->debug(sprintf(
                            '%s %s %s',
                            $modification->watchedFilename(),
                            $modification->eventNames(),
                            $modification->eventFilename()
                        ));

                        return $modification;
                    }
                }

                $exitCode = yield $this->process->join();
            });
        });
    }

    private function startProcess(): Promise
    {
        return \Amp\call(function () {
            $process = new Process([
                'inotifywait',
                $this->path,
                '-r',
                '-emodify,create',
                '--monitor',
                '--csv',
            ]);

            $pid = yield $process->start();
            $this->logger->debug(sprintf(
                'Inotify (pid:%s): %s ',
                $pid,
                $process->getCommand()
            ));

            if (!$process->isRunning()) {
                throw new RuntimeException(sprintf(
                    'Could not start process: %s',
                    $process->getCommand()
                ));
            }

            return $process;
        });
    }
}
