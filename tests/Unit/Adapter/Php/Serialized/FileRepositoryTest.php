<?php

namespace Phpactor\Indexer\Tests\Unit\Adapter\Php\Serialized;

use PHPUnit\Framework\TestCase;
use Phpactor\Indexer\Adapter\Php\Serialized\FileRepository;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Tests\IntegrationTestCase;
use Phpactor\Name\FullyQualifiedName;

class FileRepositoryTest extends IntegrationTestCase
{
    public function testResetRemovesTheIndex()
    {
        $repo = new FileRepository($this->workspace()->path('index'));
        $this->workspace()->put('index/something.cache', 'foo');
        $this->workspace()->put('index/something/else/some.cache', 'bar');

        self::assertFileExists($this->workspace()->path('index/something.cache'));
        self::assertFileExists($this->workspace()->path('index/something/else/some.cache', 'bar'));

        $repo->reset();

        self::assertFileNotExists($this->workspace()->path('index/something.cache'));
        self::assertFileNotExists($this->workspace()->path('index/something/else/some.cache', 'bar'));
    }

    public function testRemovesClassRecord(): void
    {
        $repo = new FileRepository($this->workspace()->path('index'));
        $repo->put(ClassRecord::fromName('Foobar'));
        self::assertNotNull($repo->get(ClassRecord::fromName('Foobar')));
        $repo->remove(ClassRecord::fromName('Foobar'));
        self::assertNull($repo->get(ClassRecord::fromName('Foobar')));
    }
}
