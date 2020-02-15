<?php

namespace Phpactor\WorkspaceQuery\Extension\Command;

use Phpactor\WorkspaceQuery\Model\FileListProvider;
use Phpactor\WorkspaceQuery\Model\Index;
use Phpactor\WorkspaceQuery\Model\IndexBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Phpactor\WorkspaceQuery\Util\Cast;
use Webmozart\PathUtil\Path;

class IndexBuildCommand extends Command
{
    const ARG_SUB_PATH = 'sub-path';
    const OPT_RESET = 'reset';

    /**
     * @var Index
     */
    private $index;

    /**
     * @var IndexBuilder
     */
    private $indexBuilder;

    /**
     * @var FileListProvider
     */
    private $provider;

    protected function configure(): void
    {
        $this->addArgument(self::ARG_SUB_PATH, InputArgument::OPTIONAL, 'Sub path to index');
        $this->addOption(self::OPT_RESET, null, InputOption::VALUE_NONE, 'Purge index before building');
    }

    public function __construct(IndexBuilder $indexBuilder, Index $index, FileListProvider $provider)
    {
        parent::__construct();
        $this->index = $index;
        $this->indexBuilder = $indexBuilder;
        $this->provider = $provider;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subPath = Cast::toStringOrNull($input->getArgument(self::ARG_SUB_PATH));

        if ($input->getOption(self::OPT_RESET)) {
            $this->index->reset();
        }

        if (is_string($subPath)) {
            $subPath = Path::join(
                Cast::toStringOrNull(getcwd()),
                $subPath
            );
        }

        $start = microtime(true);
        $output->writeln('<info>Building file list</info>');
        $output->write(PHP_EOL);
        $fileList = $this->provider->provideFileList($this->index, $subPath);

        $output->writeln('<info>Building index</info>');
        $progress = new ProgressBar($output, $fileList->count(), 0.001);
        foreach ($this->indexBuilder->index($fileList) as $tick) {
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

        return 0;
    }
}
