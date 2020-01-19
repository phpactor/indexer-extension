<?php

namespace Phpactor\ProjectQuery\Model;

interface IndexBuilder
{
    public function refresh(): void;
}
