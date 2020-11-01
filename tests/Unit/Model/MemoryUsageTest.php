<?php

namespace Phpactor\Indexer\Tests\Unit\Model;

use Generator;
use PHPUnit\Framework\TestCase;
use Phpactor\Indexer\Model\MemoryUsage;

class MemoryUsageTest extends TestCase
{
    public function testMemoryLimit(): void
    {
        $limit = MemoryUsage::create()->memoryLimit();
        self::assertIsInt($limit);
        $limit = MemoryUsage::create()->memoryUsageFormatted();
        var_dump($limit);
    }

    public function testMemoryUsage(): void
    {
        self::assertIsInt(MemoryUsage::create()->memoryUsage());
    }

    /**
     * @dataProvider provideFormat
     */
    public function testFormat(string $limit, int $usage, string $expected): void
    {
        self::assertEquals($expected, MemoryUsage::createFromLimitAndUsage($limit, $usage)->memoryUsageFormatted());
    }

    /**
     * @return Generator<mixed>
     */
    public function provideFormat(): Generator
    {
        yield 'infinite memory' => [
            '-1',
            0,
            '0.00 / ∞ mb'
        ];

        yield [
            '1048576',
            0,
            '0.00 / 1.05 mb'
        ];

        yield [
            '1000000',
            1000000,
            '1.00 / 1.00 mb'
        ];

        yield [
            '1000K',
            1000000,
            '1.00 / 1.00 mb'
        ];

        yield [
            '1M',
            1000000,
            '1.00 / 1.00 mb'
        ];

        yield [
            '100M',
            1000000,
            '1.00 / 100.00 mb'
        ];

        yield [
            '1G',
            1000000,
            '1.00 / 1,000.00 mb'
        ];
    }
}
