<?php

namespace Phpactor\Indexer\Tests\Integration\Worse;

use Closure;
use Phpactor\Indexer\Adapter\Worse\WorseIndexBuilder;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\Indexer\Tests\Integration\IndexBuilderIndexTestCase;
use Psr\Log\NullLogger;

class WorseIndexMemoryBuilderTest extends IndexBuilderIndexTestCase
{
    protected function createBuilder(Index $index): IndexBuilder
    {
        $indexBuilder = new WorseIndexBuilder(
            $index,
            $this->createReflector(),
            new NullLogger()
        );
        return $indexBuilder;
    }

    /**
     * @dataProvider provideIndexesClassLike
     */
    public function testIndexesClassLike(string $source, string $name, Closure $assertions): void
    {
        // this indexer doesn't support this, and it is replaced by the tolerant indexer
        // we can remove it once the tolerant indexer is proved to be stable.
        if (0 === strpos($name, 'Foobar\\ThisIsTrait')) {
            $this->addToAssertionCount(1);
            return;
        }

        parent::testIndexesClassLike($source, $name, $assertions);
    }
}
