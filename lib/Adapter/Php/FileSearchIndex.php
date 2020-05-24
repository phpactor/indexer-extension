<?php

namespace Phpactor\Indexer\Adapter\Php;

use Generator;
use Phpactor\Indexer\Model\Matcher;
use Phpactor\Indexer\Model\Record;
use Phpactor\Indexer\Model\RecordFactory;
use Phpactor\Indexer\Model\SearchIndex;
use function Safe\file_get_contents;

class FileSearchIndex implements SearchIndex
{
    private const DELIMITER = "\t";

    /**
     * @var bool
     */
    private $open = false;

    /**
     * @var array<string>
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

        foreach ($this->subjects as $compoundIdentifier) {
            [ $recordType, $identifier ] = explode(self::DELIMITER, $compoundIdentifier);

            if (false === $this->matcher->match($identifier, $query)) {
                continue;
            }

            yield RecordFactory::create($recordType, $identifier);
        }
    }

    public function write(Record $record): void
    {
        $this->subjects[] = $record->recordType() . self::DELIMITER . $record->identifier();
    }

    public function flush(): void
    {
        file_put_contents($this->path, implode("\n", array_unique($this->subjects)));
    }

    private function open(): void
    {
        if (!file_exists($this->path)) {
            return;
        }

        $this->subjects = explode("\n", file_get_contents($this->path));
        $this->open = true;
    }
}
