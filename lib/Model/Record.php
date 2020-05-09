<?php

namespace Phpactor\Indexer\Model;

abstract class Record
{
    /**
     * Return string which is unique to this record (used for namespacing),
     * e.g. "class".
     */
    abstract public function recordType(): string;

    abstract public function identifier(): string;
}
