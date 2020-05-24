<?php

namespace Phpactor\Indexer\Adapter\Php;

use Generator;
use Phpactor\Indexer\Model\Matcher;
use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\RecordFactory;
use Phpactor\Indexer\Model\SearchIndex;
use function Safe\file_get_contents;
use function Safe\file_put_contents;

class FileSearchIndex implements SearchIndex
{
    private const DELIMITER = "\t";

    /**
     * @var bool
     */
    private $open = false;

    /**
     * @var array<array{string,string}>
     */
    private $subjects = [];

    /**
     * @var string
     */
    private $path;

    /**
     * @var Matcher
     */
    private $matcher;

    public function __construct(string $path, Matcher $matcher)
    {
        $this->path = $path;
        $this->matcher = $matcher;
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $query): Generator
    {
        if (false === $this->open) {
            $this->open();
        }

        foreach ($this->subjects as [ $recordType, $identifier ]) {
            if (false === $this->matcher->match($identifier, $query)) {
                continue;
            }

            yield RecordFactory::create($recordType, $identifier);
        }
    }

    public function write(Record $record): void
    {
        $this->subjects[] = [$record->recordType(), $record->identifier()];
    }

    public function flush(): void
    {
        file_put_contents($this->path, implode("\n", array_unique(array_map(function (array $parts) {
            return implode(self::DELIMITER, $parts);
        }, $this->subjects))));
    }

    private function open(): void
    {
        if (!file_exists($this->path)) {
            return;
        }

        $this->subjects = array_map(function (string $line) {
            return explode(self::DELIMITER, $line);
        }, explode("\n", file_get_contents($this->path)));
        $this->open = true;
    }
}
