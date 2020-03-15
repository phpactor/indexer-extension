<?php

namespace Phpactor\WorkspaceQuery\Adapter\Amp;

use Amp\Promise;

interface Watcher
{
    /**
     * Return a promise with a FileModification object.
     */
    public function wait(): Promise;
}
