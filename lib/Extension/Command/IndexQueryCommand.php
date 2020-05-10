<?php

namespace Phpactor\Indexer\Extension\Command;

use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Indexer\Model\Record\MemberRecord;
use Phpactor\Indexer\Model\IndexQueryAgent;
use Phpactor\Indexer\Util\Cast;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexQueryCommand extends Command
{
    const ARG_QUERY = 'query';

    /**
     * @var IndexQueryAgent
     */
    private $query;

    public function __construct(IndexQueryAgent $query)
    {
        $this->query = $query;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARG_QUERY, InputArgument::REQUIRED, 'Query (function name, class name, <memberType>#<memberName>)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $this->query->class()->get(Cast::toString($input->getArgument(self::ARG_QUERY)));

        if ($class) {
            $this->renderClass($output, $class);
        }

        $function = $this->query->function()->get(
            Cast::toString($input->getArgument(self::ARG_QUERY))
        );

        if ($function) {
            $this->renderFunction($output, $function);
        }

        $member = $this->query->member(
            Cast::toString($input->getArgument(self::ARG_QUERY))
        );

        if ($member) {
            $this->renderMember($output, $member);
        }

        return 0;
    }

    private function renderClass(OutputInterface $output, ClassRecord $class): void
    {
        $output->writeln('<info>Class:</>'.$class->fqn());
        $output->writeln('<info>Path:</>'.$class->filePath());
        $output->writeln('<info>Implements</>:');
        foreach ($class->implements() as $fqn) {
            $output->writeln(' - ' . (string)$fqn);
        }
        $output->writeln('<info>Implementations</>:');
        foreach ($class->implementations() as $fqn) {
            $output->writeln(' - ' . (string)$fqn);
        }
        $output->writeln('<info>Referenced by</>:');
        foreach ($class->references() as $path) {
            $file = $this->query->file()->get($path);
            $output->writeln(sprintf('- %s:%s', $path, implode(', ', array_map(function (RecordReference $reference) {
                return $reference->offset();
            }, $file->referencesTo($class)->toArray()))));
        }
    }

    private function renderFunction(OutputInterface $output, FunctionRecord $function): void
    {
        $output->writeln('<info>Function:</>'.$function->fqn());
        $output->writeln('<info>Path:</>'.$function->filePath());
        $output->writeln('<info>Referenced by</>:');
        foreach ($function->references() as $path) {
            $file = $this->query->file()->get($path);
            $output->writeln(sprintf('- %s:%s', $path, implode(', ', array_map(function (RecordReference $reference) {
                return $reference->offset();
            }, $file->referencesTo($function)->toArray()))));
        }
    }

    private function renderMember(OutputInterface $output, MemberRecord $member): void
    {
        $output->writeln('<info>Member:</>'.$member->memberName());
        $output->writeln('<info>Member Type:</>'.$member->type());
        $output->writeln('<info>Referenced by</>:');
        foreach ($member->references() as $path) {
            $file = $this->query->file()->get($path);
            $output->writeln(sprintf('- %s:%s', $path, implode(', ', array_map(function (RecordReference $reference) {
                return sprintf(
                    '[<comment>%s</>:<info>%s</>]',
                    $reference->contaninerType() ?: '???',
                    $reference->offset()
                );
            }, $file->referencesTo($member)->toArray()))));
        }
    }
}
