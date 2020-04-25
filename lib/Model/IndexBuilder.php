<?php

namespace Phpactor\Indexer\Model;

use Generator;
use SplFileInfo;

interface IndexBuilder
{
    public function index(SplFileInfo $info): void;

    public function done(): void;
}
