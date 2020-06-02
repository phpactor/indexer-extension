<?php

namespace Phpactor\Indexer\Extension\Command;

use Amp\Loop;
use Phpactor\AmpFsWatch\Watcher;
use Phpactor\Indexer\Model\Indexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Phpactor\Indexer\Util\Cast;
use Webmozart\PathUtil\Path;

class IndexBuildCommand extends Command
{
    const ARG_SUB_PATH = 'sub-path';
    const OPT_RESET = 'reset';
    const OPT_WATCH = 'watch';

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var Watcher
     */
    private $watcher;

    public function __construct(Indexer $indexer, Watcher $watcher)
    {
        parent::__construct();
        $this->indexer = $indexer;
        $this->watcher = $watcher;
    }

    protected function configure(): void
    {
        $this->setDescription('Build the index');
        $this->addArgument(self::ARG_SUB_PATH, InputArgument::OPTIONAL, 'Sub path to index');
        $this->addOption(self::OPT_RESET, null, InputOption::VALUE_NONE, 'Purge index before building');
        $this->addOption(self::OPT_WATCH, null, InputOption::VALUE_NONE, 'Watch for updated files (poll for changes ever x seconds, default 10)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subPath = Cast::toStringOrNull($input->getArgument(self::ARG_SUB_PATH));
        $watch = Cast::toBool($input->getOption(self::OPT_WATCH));

        if ($input->getOption(self::OPT_RESET)) {
            $this->indexer->reset();
        }

        if (is_string($subPath)) {
            $subPath = Path::join(
                Cast::toStringOrNull(getcwd()),
                $subPath
            );
        }

        $this->buildIndex($output, $subPath);

        if ($watch) {
            $this->watch($output);
        }

        return 0;
    }

    private function buildIndex(OutputInterface $output, ?string $subPath = null): void
    {
        $start = microtime(true);

        $output->write('<info>Building job</info>...');
        $job = $this->indexer->getJob($subPath);
        $output->writeln('done');
        $output->writeln('<info>Building index:</info>');
        $output->write(PHP_EOL);
        $progress = new ProgressBar($output, $job->size(), 0.001);
        foreach ($job->generator() as $filePath) {
            if ($output->isVerbose()) {
                $output->writeln(sprintf('Updated %s', $filePath));
                continue;
            }
            $progress->advance();
        }

        $progress->finish();
        $output->write(PHP_EOL);
        $output->write(PHP_EOL);

        $output->writeln(sprintf(
            '<bg=green;fg=black;option>Done in %s seconds using %sb of memory</>',
            number_format(microtime(true) - $start, 2),
            number_format(memory_get_usage(true))
        ));
    }

    private function watch(OutputInterface $output): void
    {
        Loop::run(function () use ($output) {
            $process = yield $this->watcher->watch();

            Loop::onSignal(SIGINT, function () use ($output, $process) {
                $output->write('Shutting down watchers...');
                $process->stop();
                $output->writeln('done');
                Loop::stop();
            });

            while (null !== $file = yield $process->wait()) {
                $job = $this->indexer->getJob($file->path());
                foreach ($job->generator() as $filePath) {
                    $output->writeln(sprintf('Updating %s', $filePath));
                }
            }
        });
    }
}
