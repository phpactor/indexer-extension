<?php

namespace Phpactor\ProjectQuery\Model;

interface Record
{
    public function lastModified(): int;
}
