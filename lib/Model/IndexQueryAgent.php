<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Indexer\Model\Query\ClassQuery;
use Phpactor\Indexer\Model\Query\FunctionQuery;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Model\Record\MemberRecord;

class IndexQueryAgent
{
    /**
     * @var Index
     */
    private $index;

    /**
     * @var ClassQuery
     */
    private $classQuery;

    /**
     * @var FunctionQuery
     */
    private $functionQuery;

    public function __construct(Index $index)
    {
        $this->index = $index;
        $this->classQuery = new ClassQuery($index);
        $this->functionQuery = new FunctionQuery($index);
    }

    public function class(): ClassQuery
    {
        return $this->classQuery;
    }

    public function function(): FunctionQuery
    {
        return $this->functionQuery;
    }

    public function file(string $path): ?FileRecord
    {
        return $this->index->get(FileRecord::fromPath($path));
    }

    public function member(string $name): ?MemberRecord
    {
        if (!MemberRecord::isIdentifier($name)) {
            return null;
        }

        return $this->index->get(MemberRecord::fromIdentifier($name));
    }
}
