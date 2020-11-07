<?php

namespace Phpactor\Indexer\Extension\Command;

use Phpactor\Indexer\Model\Query\Criteria\ShortNameBeginsWith;
use Phpactor\Indexer\Model\SearchClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexSearchCommand extends Command
{
    const ARG_SEARCH = 'search';

    /**
     * @var SearchClient
     */
    private $searchClient;

    public function __construct(SearchClient $searchClient)
    {
        parent::__construct();
        $this->searchClient = $searchClient;
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARG_SEARCH, InputArgument::REQUIRED, 'Search text');
        $this->setDescription(
            'Search the index'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $search = $input->getArgument(self::ARG_SEARCH);
        assert(is_string($search));

        $criteria = new ShortNameBeginsWith($search);
        foreach ($this->searchClient->search($criteria) as $result) {
            $output->writeln(sprintf('<comment>%s</> <fg=cyan>#</> %s', $result->recordType(), $result->identifier()));
        }
        return 0;
    }
}
