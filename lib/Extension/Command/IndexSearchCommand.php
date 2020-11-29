<?php

namespace Phpactor\Indexer\Extension\Command;

use Phpactor\Indexer\Model\Query\Criteria\AndCriteria;
use Phpactor\Indexer\Model\Query\Criteria\FqnBeginsWith;
use Phpactor\Indexer\Model\Query\Criteria\IsClass;
use Phpactor\Indexer\Model\Query\Criteria\IsFunction;
use Phpactor\Indexer\Model\Query\Criteria\IsMember;
use Phpactor\Indexer\Model\Query\Criteria\ShortNameBeginsWith;
use Phpactor\Indexer\Model\SearchClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IndexSearchCommand extends Command
{
    const ARG_SEARCH = 'search';
    const OPT_FQN_BEGINS = 'fqn-begins';
    const OPT_SHORT_NAME_BEGINS = 'short-name-begins';
    const OPT_IS_FUNCTION = 'is-function';
    const OPT_IS_CLASS = 'is-class';
    const OPT_IS_MEMBER = 'is-member';


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
        $this->addOption(self::OPT_FQN_BEGINS, null, InputOption::VALUE_REQUIRED, 'FQN begins with');
        $this->addOption(self::OPT_SHORT_NAME_BEGINS, null, InputOption::VALUE_REQUIRED, 'Short-name begins with');
        $this->addOption(self::OPT_IS_FUNCTION, null, InputOption::VALUE_NONE, 'Functions only');
        $this->addOption(self::OPT_IS_CLASS, null, InputOption::VALUE_NONE, 'Classes only');
        $this->addOption(self::OPT_IS_MEMBER, null, InputOption::VALUE_NONE, 'Is Member');
        $this->setDescription(
            'Search the index'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shortNameBegins = $input->getOption(self::OPT_SHORT_NAME_BEGINS);
        $fqnBegins = $input->getOption(self::OPT_FQN_BEGINS);
        $isFunction = $input->getOption(self::OPT_IS_FUNCTION);
        $isClass = $input->getOption(self::OPT_IS_CLASS);
        $isMember = $input->getOption(self::OPT_IS_MEMBER);
        assert(is_string($search));

        $criterias = [];

        if ($shortNameBegins) {
            $criterias[] = new ShortNameBeginsWith($shortNameBegins);
        }

        if ($fqnBegins) {
            $criterias[] = new FqnBeginsWith($fqnBegins);
        }

        if ($isFunction) {
            $criterias[] = new IsFunction();
        }

        if ($isMember) {
            $criterias[] = new IsMember();
        }

        if ($isClass) {
            $criterias[] = new IsClass();
        }

        foreach ($this->searchClient->search(new AndCriteria(...$criterias)) as $result) {
            $output->writeln(sprintf('<comment>%s</> <fg=cyan>#</> %s', $result->recordType(), $result->identifier()));
        }

        return 0;
    }
}
