<?php

namespace Phpactor\ProjectQuery\Model;

use Generator;

interface IndexBuilder
{
    /**
     * @return Generator<string>
     */
    public function build(?string $subPath = null): Generator;
}
