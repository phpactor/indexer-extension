<?php

namespace Phpactor\Indexer\Tests\Unit\Adapter\Worse;

use PHPUnit\Framework\TestCase;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\Indexer\Adapter\Php\InMemory\InMemoryIndex;
use Phpactor\Indexer\Adapter\Worse\IndexerFunctionSourceLocator;
use Phpactor\Indexer\Model\Record\FunctionRecord;
use Phpactor\WorseReflection\Core\Exception\SourceNotFound;
use Phpactor\WorseReflection\Core\Name;

class IndexerFunctionSourceLocatorTest extends TestCase
{
    public function testThrowsExceptionIfFunctionNotInIndex(): void
    {
        $this->expectException(SourceNotFound::class);
        $index = new InMemoryIndex();
        $locator = new IndexerFunctionSourceLocator($index);
        $locator->locate(Name::fromString('Foobar'));
    }

    public function testThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectException(SourceNotFound::class);
        $this->expectExceptionMessage('does not exist');
        $record = new FunctionRecord(
            FullyQualifiedName::fromString('Foobar')
        );
        $record->withFilePath('nope.php');
        $index = new InMemoryIndex();
        $index->write()->function($record);
        $locator = new IndexerFunctionSourceLocator($index);
        $locator->locate(Name::fromString('Foobar'));
    }

    public function testReturnsSourceCode(): void
    {
        $record = new FunctionRecord(
            FullyQualifiedName::fromString('Foobar')
        );
        $record->withFilePath(__FILE__);
        $index = new InMemoryIndex();
        $index->write()->function($record);
        $locator = new IndexerFunctionSourceLocator($index);
        $sourceCode = $locator->locate(Name::fromString('Foobar'));
        $this->assertEquals(__FILE__, $sourceCode->path());
    }
}
