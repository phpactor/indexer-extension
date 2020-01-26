<?php

namespace Phpactor\ProjectQuery\Tests\Integration\Php;

use Phpactor\ProjectQuery\Adapter\Php\Serialized\FileRepository;
use Phpactor\ProjectQuery\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\ProjectQuery\Model\Index;
use Phpactor\ProjectQuery\Tests\Integration\IndexTestCase;

class SerializedIndexTest extends IndexTestCase
{
    protected function createIndex(): Index
    {
        return new SerializedIndex(new FileRepository(
            $this->workspace()->path('cache')
        ));
    }
}
