<?php

namespace Phpactor\Indexer\Model\Query;

use Generator;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\RecordReferenceEnhancer;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\TextDocument\Location;
use Phpactor\Indexer\Model\Record\MemberRecord;

class MemberQuery implements IndexQuery
{
    /**
     * @var Index
     */
    private $index;

    /**
     * @var RecordReferenceEnhancer
     */
    private $enhancer;

    public function __construct(Index $index, RecordReferenceEnhancer $enhancer)
    {
        $this->index = $index;
        $this->enhancer = $enhancer;
    }

    public function get(string $identifier): ?MemberRecord
    {
        if (!MemberRecord::isIdentifier($identifier)) {
            return null;
        }

        $prototype = MemberRecord::fromIdentifier($identifier);

        if (false === $this->index->has($prototype)) {
            return null;
        }

        return $this->index->get($prototype);
    }

    public function getByTypeAndName(string $type, string $name): ?MemberRecord
    {
        return $this->get($type . '#' . $name);
    }

    /**
     * @return Generator<Location>
     */
    public function referencesTo(string $type, string $memberName): Generator
    {
        $record = $this->getByTypeAndName($type, $memberName);
        assert($record instanceof MemberRecord);

        foreach ($record->references() as $fileReference) {
            $fileRecord = $this->index->get(FileRecord::fromPath($fileReference));
            assert($fileRecord instanceof FileRecord);

            foreach ($fileRecord->references()->to($record) as $memberReference) {
                $memberReference = $this->enhancer->enhance($fileRecord, $memberReference);

                if (null === $memberReference->contaninerType()) {
                    continue;
                }

                yield Location::fromPathAndOffset($fileRecord->filePath(), $memberReference->offset());
            }
        }
    }
}
