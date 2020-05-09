<?php

namespace Phpactor\Indexer\Extension\Command;

use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Util\Cast;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexQueryCommand extends Command
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $this->query->class(
            FullyQualifiedName::fromString(
                Cast::toString($input->getArgument(self::ARG_FQN))
            )
        );

        if ($class) {
            $this->renderClass($output, $class);
        }

        $function = $this->query->function(
            FullyQualifiedName::fromString(
                Cast::toString($input->getArgument(self::ARG_FQN))
            )
        );

        if ($function) {
            $this->renderFunction($output, $function);
        }

        return 0;
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARG_FQN, InputArgument::REQUIRED, 'Fully qualified name');
    }

    private function renderClass(OutputInterface $output, ClassRecord $class): void
    {
        $output->writeln('<info>Class:</>'.$class->fqn());
        $output->writeln('<info>Path:</>'.$class->filePath());
        $output->writeln('<info>Last modified:</>'.$class->lastModified());
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
            $file = $this->query->file($path);
            $output->writeln(sprintf('- %s:%s', $path, implode(', ', array_map(function (RecordReference $reference) {
                return $reference->offset();
            }, $file->referencesTo($class)->toArray()))));
        }
    }

    private function renderFunction(OutputInterface $output, FunctionRecord $function): void
    {
        $output->writeln('<info>Function:</>'.$function->fqn());
        $output->writeln('<info>Path:</>'.$function->filePath());
        $output->writeln('<info>Last modified:</>'.$function->lastModified());
        $output->writeln('<info>Referenced by</>:');
        foreach ($function->references() as $path) {
            $file = $this->query->file($path);
            $output->writeln(sprintf('- %s:%s', $path, implode(', ', array_map(function (RecordReference $reference) {
                return $reference->offset();
            }, $file->referencesTo($function)->toArray()))));
        }
    }
}
