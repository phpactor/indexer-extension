<?php

namespace Phpactor\ProjectQuery\Model;

use DateTimeImmutable;

interface Index
{
    public function lastUpdate(): DateTimeImmutable;

    public function query(): IndexQuery;

    public function write(): IndexWriter;
}
