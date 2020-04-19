<?php

namespace Phpactor\Indexer\Tests\Unit\Adapter\Worse;

use PHPUnit\Framework\TestCase;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\Indexer\Adapter\Php\InMemory\InMemoryIndex;
use Phpactor\Indexer\Adapter\Worse\IndexerClassSourceLocator;
use Phpactor\Indexer\Model\Record\ClassRecord;
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
        $record = new ClassRecord(
            0,
            FullyQualifiedName::fromString('Foobar'),
            'class',
            ByteOffset::fromInt(0),
            'nope.php'
        );
        $index = new InMemoryIndex();
        $index->write()->class($record);
        $locator = new IndexerClassSourceLocator($index);
        $locator->locate(Name::fromString('Foobar'));
    }

    public function testReturnsSourceCode(): void
    {
        $record = new ClassRecord(
            0,
            FullyQualifiedName::fromString('Foobar'),
            'class',
            ByteOffset::fromInt(0),
            __FILE__
        );
        $index = new InMemoryIndex();
        $index->write()->class($record);
        $locator = new IndexerClassSourceLocator($index);
        $sourceCode = $locator->locate(Name::fromString('Foobar'));
        $this->assertEquals(__FILE__, $sourceCode->path());
    }
}
