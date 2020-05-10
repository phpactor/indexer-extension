<?php

namespace Phpactor\Indexer\Adapter\Worse;

use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\RecordReferenceEnhancer;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Model\Record\MemberRecord;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use Safe\Exceptions\FilesystemException;
use function Safe\file_get_contents;

class WorseRecordReferenceEnhancer implements RecordReferenceEnhancer
{
    /**
     * @var SourceCodeReflector
     */
    private $reflector;

    public function __construct(SourceCodeReflector $reflector)
    {
        $this->reflector = $reflector;
    }

    public function enhance(FileRecord $record, RecordReference $reference): RecordReference
    {
        if ($reference->type() !== MemberRecord::RECORD_TYPE) {
            return $reference;
        }

        try {
            // TODO: We should get the latest in-memory source, e.g. from the
            // LS workspace. Perhaps add an adapter.
            $contents = file_get_contents($record->filePath());
        } catch (FilesystemException $error) {
            return $reference;
        }

        $offset = $this->reflector->reflectOffset($contents, $reference->offset());
        $containerType = $offset->symbolContext()->containerType();

        if (null === $containerType) {
            return $reference;
        }

        return $reference->withContainerType($containerType);
    }
}
