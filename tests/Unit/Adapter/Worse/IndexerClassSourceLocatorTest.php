<?php

namespace Phpactor\Indexer\Tests\Unit\Adapter\Worse;

use PHPUnit\Framework\TestCase;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\Indexer\Adapter\Php\InMemory\InMemoryIndex;
use Phpactor\Indexer\Adapter\Worse\IndexerClassSourceLocator;
use Phpactor\Indexer\Model\Record;
use Phpactor\WorseReflection\Core\Exception\SourceNotFound;
use Phpactor\WorseReflection\Core\Name;

class IndexerClassSourceLocatorTest extends TestCase
{
    public function testThrowsExceptionIfClassNotInIndex(): void
    {
        $this->expectException(SourceNotFound::class);
        $index = new InMemoryIndex();
        $locator = new IndexerClassSourceLocator($index);
        $locator->locate(Name::fromString('Foobar'));
    }

    public function testThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectException(SourceNotFound::class);
        $this->expectExceptionMessage('does not exist');
        $record = ClassRecord::fromName('Foobar')
            ->setType('class')
            ->setStart(ByteOffset::fromInt(0))
            ->setFilePath('nope.php')
            ->setLastModified(0);

        $index = new InMemoryIndex();
        $index->write($record);
        $locator = new IndexerClassSourceLocator($index);
        $locator->locate(Name::fromString('Foobar'));
    }

    public function testReturnsSourceCode(): void
    {
        $record = ClassRecord::fromName('Foobar')
            ->setType('class')
            ->setStart(ByteOffset::fromInt(0))
            ->setFilePath(__FILE__)
            ->setLastModified(0);

        $index = new InMemoryIndex();
        $index->write($record);
        $locator = new IndexerClassSourceLocator($index);
        $sourceCode = $locator->locate(Name::fromString('Foobar'));
        $this->assertEquals(__FILE__, $sourceCode->path());
    }
}
