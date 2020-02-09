<?php

namespace Phpactor\WorkspaceQuery\Tests\Integration\Php;

use Phpactor\WorkspaceQuery\Adapter\Php\Serialized\FileRepository;
use Phpactor\WorkspaceQuery\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\WorkspaceQuery\Model\Index;
use Phpactor\WorkspaceQuery\Tests\Integration\IndexTestCase;

class SerializedIndexTest extends IndexTestCase
{
    protected function createIndex(): Index
    {
        return new SerializedIndex(new FileRepository(
            $this->workspace()->path('cache')
        ));
    }
}
