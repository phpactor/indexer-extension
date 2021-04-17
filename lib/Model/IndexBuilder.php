<?php

namespace Phpactor\Indexer\Model;

use Phpactor\TextDocument\TextDocument;
use SplFileInfo;

interface IndexBuilder
{
    public function index(TextDocument $document): void;

    public function done(): void;
}
