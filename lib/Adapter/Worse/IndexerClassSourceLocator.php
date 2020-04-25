<?php

namespace Phpactor\Indexer\Adapter\Worse;

use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\Index;
use Phpactor\WorseReflection\Core\Exception\SourceNotFound;
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\SourceCodeLocator;

class IndexerClassSourceLocator implements SourceCodeLocator
{
    /**
     * @var Index
     */
    private $index;

    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    /**
     * {@inheritDoc}
     */
    public function locate(Name $name): SourceCode
    {
        if (empty($name->__toString())) {
            throw new SourceNotFound('Name is empty');
        }

        $record = $this->index->query()->class(
            FullyQualifiedName::fromString($name->__toString())
        );

        if (null === $record) {
            throw new SourceNotFound(sprintf(
                'Class "%s" not in index',
                $name->full()
            ));
        }

        $filePath = $record->filePath();

        if (null === $filePath || !file_exists($filePath)) {
            throw new SourceNotFound(sprintf(
                'Class "%s" is indexed, but it does not exist at path "%s"!',
                $name->full(),
                $filePath
            ));
        }

        return SourceCode::fromPath($filePath);
    }
}
