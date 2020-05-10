<?php

namespace Phpactor\Indexer\Model\Query;

use Generator;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexQuery;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\TextDocument\Location;
use Phpactor\Indexer\Model\Record\MemberRecord;

class MemberQuery implements IndexQuery
{
    /**
     * @var Index
     */
    private $index;

    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    public function get(string $identifier): ?MemberRecord
    {
        if (!MemberRecord::isIdentifier($identifier)) {
            return null;
        }

        return $this->index->get(MemberRecord::fromIdentifier($identifier));
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

            foreach ($fileRecord->references()->to($record) as $classReference) {
                yield Location::fromPathAndOffset($fileRecord->filePath(), $classReference->offset());
            }
        }
    }
}
