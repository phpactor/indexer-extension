<?php

namespace Phpactor\ProjectQuery\Adapter\Symfony\Console;

use Phpactor\ProjectQuery\Model\Index;
use Phpactor\ProjectQuery\Model\IndexBuilder;
use Phpactor\ProjectQuery\Model\IndexWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexRefreshCommand extends Command
{
    const ARG_SUB_PATH = 'sub-path';

    /**
     * @var IndexBuilder
     */
    private $index;

    public function __construct(IndexBuilder $index)
    {
        $this->index = $index;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $index = 0;
        foreach ($this->index->build($input->getArgument(self::ARG_SUB_PATH)) as $tick) {
            if (++$index % 500 === 0) {
                $output->writeln(sprintf('Ticks %s', $index));
            }
        }
        $output->writeln(sprintf('Ticks %s', $index));
        return 0;
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARG_SUB_PATH, InputArgument::OPTIONAL, 'Sub path to index');
    }
}
