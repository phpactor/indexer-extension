<?php

namespace Phpactor\ProjectQuery\Adapter\Symfony\Console;

use Phpactor\ProjectQuery\Model\Index;
use Phpactor\ProjectQuery\Model\IndexBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Phpactor\ProjectQuery\Util\Cast;

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
        if ($input->getOption(self::OPT_RESET)) {
            $this->index->reset();
        }
        $start = microtime(true);
        $index = 0;
        foreach ($this->indexBuilder->build(
            Cast::toStringOrNull($input->getArgument(self::ARG_SUB_PATH))
        ) as $tick) {
            if (++$index % 500 === 0) {
                $output->writeln('.');
            }
        }
        $output->writeln(sprintf('<bg=green;fg=black;option>Done (%s operations in %s seconds)</>', $index, number_format(microtime(true) - $start, 2)));
        return 0;
    }
}
