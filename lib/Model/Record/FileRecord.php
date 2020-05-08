<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\Record;
use SplFileInfo;

class FileRecord extends Record
{
    public function __construct(string $filePath)
    {
        $this->setFilePath($filePath);
    }

    /**
     * {@inheritDoc}
     */
    public function recordType(): string
    {
        return 'file';
    }

    public static function fromFileInfo(SplFileInfo $info): self
    {
        return new self($info->getPathname());
    }

    public function identifier(): string
    {
        return $this->filePath();
    }
}
