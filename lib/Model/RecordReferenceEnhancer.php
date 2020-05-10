<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Indexer\Model\Record\FileRecord;

/**
 * Add additional information to the record reference, e.g. detime
 * it's container type through static analysis.
 */
interface RecordReferenceEnhancer
{
    public function enhance(FileRecord $record, RecordReference $reference): RecordReference;
}
