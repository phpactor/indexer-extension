<?php

namespace Phpactor\Indexer\Extension\Command;

use Phpactor\Indexer\Model\Indexer;
use RuntimeException;
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
    const OPT_INTERVAL = 'interval';
    const DEFAULT_REFRESH_INTERVAL = 5;

    /**
     * @var Indexer
     */
    private $indexer;

    public function __construct(Indexer $indexer)
    {
        parent::__construct();
        $this->indexer = $indexer;
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARG_SUB_PATH, InputArgument::OPTIONAL, 'Sub path to index');
        $this->addOption(self::OPT_RESET, null, InputOption::VALUE_NONE, 'Purge index before building');
        $this->addOption(self::OPT_WATCH, null, InputOption::VALUE_NONE, 'Watch for updated files (poll for changes ever x seconds, default 10)');
        $this->addOption(self::OPT_INTERVAL, null, InputOption::VALUE_REQUIRED, 'Interval (in seconds) to poll filesystem for changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subPath = Cast::toStringOrNull($input->getArgument(self::ARG_SUB_PATH));
        $watch = Cast::toBool($input->getOption(self::OPT_WATCH));
        $interval = Cast::toIntOrNull($input->getOption(self::OPT_INTERVAL)) ?? self::DEFAULT_REFRESH_INTERVAL;

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
            if ($interval < 1) {
                throw new RuntimeException(sprintf(
                    'Interval must be greater or equal to 1 second'
                ));
            }

            $this->watch($output, $subPath, $interval);
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

    private function watch(OutputInterface $output, ?string $subPath, int $interval): void
    {
        $output->writeln(sprintf('Polling for changes every %s seconds', $interval));
        while (true) {
            $job = $this->indexer->getJob($subPath);
            foreach ($job->generator() as $filePath) {
                $output->writeln(sprintf('Updating %s', $filePath));
            }
            sleep($interval);
        }
    }
}
