<?php

namespace Phpactor\Indexer\Adapter\Worse;

use Phpactor\Indexer\Model\IndexQueryAgent;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\WorseReflection\Core\Exception\SourceNotFound;
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\SourceCodeLocator;

class IndexerFunctionSourceLocator implements SourceCodeLocator
{
    /**
     * @var IndexQueryAgent
     */
    private $query;

    public function __construct(IndexQueryAgent $query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritDoc}
     */
    public function locate(Name $name): SourceCode
    {
        if (empty($name->__toString())) {
            throw new SourceNotFound('Name is empty');
        }

        $record = $this->query->function(
            FullyQualifiedName::fromString($name->__toString())
        );

        if (null === $record) {
            throw new SourceNotFound(sprintf(
                'Function "%s" not in index',
                $name->full()
            ));
        }

        $filePath = $record->filePath();

        if (!file_exists($filePath)) {
            throw new SourceNotFound(sprintf(
                'Function "%s" is indexed, but it does not exist at path "%s"!',
                $name->full(),
                $filePath
            ));
        }

        return SourceCode::fromPath($filePath);
    }
}
