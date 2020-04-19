<?php

namespace Phpactor\Indexer\Extension\Command;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Util\Cast;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexQueryFunctionCommand extends Command
{
    const ARG_FQN = 'fqn';

    /**
     * @var IndexQuery
     */
    private $query;

    public function __construct(IndexQuery $query)
    {
        $this->query = $query;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARG_FQN, InputArgument::REQUIRED, 'Fully qualified name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $function = $this->query->function(
            FullyQualifiedName::fromString(
                Cast::toString($input->getArgument(self::ARG_FQN))
            )
        );
        if (!$function) {
            $output->writeln('Function not found');
            return 1;
        }
        $output->writeln('<info>Function:</>'.$function->fqn());
        $output->writeln('<info>Path:</>'.$function->filePath());
        $output->writeln('<info>Last modified:</>'.$function->lastModified());
        return 0;
    }
}
