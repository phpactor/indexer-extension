<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Indexer\Model\Query\ClassQuery;
use Phpactor\Indexer\Model\Query\FileQuery;
use Phpactor\Indexer\Model\Query\FunctionQuery;
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

    /**
     * @var FileQuery
     */
    private $fileQuery;

    public function __construct(Index $index)
    {
        $this->index = $index;
        $this->classQuery = new ClassQuery($index);
        $this->functionQuery = new FunctionQuery($index);
        $this->fileQuery = new FileQuery($index);
    }

    public function class(): ClassQuery
    {
        return $this->classQuery;
    }

    public function function(): FunctionQuery
    {
        return $this->functionQuery;
    }

    public function file(): FileQuery
    {
        return $this->fileQuery;
    }

    public function member(string $name): ?MemberRecord
    {
        if (!MemberRecord::isIdentifier($name)) {
            return null;
        }

        return $this->index->get(MemberRecord::fromIdentifier($name));
    }
}
