<?php

namespace Phpactor\WorkspaceQuery\Adapter\Symfony\Console;

use Phpactor\WorkspaceQuery\Model\Index;
use Phpactor\WorkspaceQuery\Model\IndexBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Phpactor\WorkspaceQuery\Util\Cast;
use Webmozart\PathUtil\Path;

class IndexRefreshCommand extends Command
{
    const ARG_SUB_PATH = 'sub-path';
    const OPT_RESET = 'rebuild';

    /**
     * @var Index
     */
    private $index;

    /**
     * @var IndexBuilder
     */
    private $indexBuilder;

    protected function configure(): void
    {
        $this->addArgument(self::ARG_SUB_PATH, InputArgument::OPTIONAL, 'Sub path to index');
        $this->addOption(self::OPT_RESET, null, InputOption::VALUE_NONE, 'Purge index before building');
    }

    public function __construct(IndexBuilder $indexBuilder, Index $index)
    {
        parent::__construct();
        $this->index = $index;
        $this->indexBuilder = $indexBuilder;
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
        $index = 0;
        foreach ($this->indexBuilder->build($subPath) as $tick) {
            if (++$index % 500 === 0) {
                $output->writeln('.');
            }
        }
        $output->writeln(sprintf(
            '<bg=green;fg=black;option>Done (%s operations in %s seconds, %sb of memory)</>',
            $index,
            number_format(microtime(true) - $start, 2),
            number_format(memory_get_peak_usage())
        ));
        return 0;
    }
}
