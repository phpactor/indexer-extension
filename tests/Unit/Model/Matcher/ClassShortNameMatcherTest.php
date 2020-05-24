<?php

namespace Phpactor\Indexer\Tests\Unit\Model\Matcher;

use Generator;
use PHPUnit\Framework\TestCase;
use Phpactor\Indexer\Model\Matcher\ClassShortNameMatcher;

class ClassShortNameMatcherTest extends TestCase
{
    /**
     * @dataProvider provideMatch
     */
    public function testMatch(string $query, string $subject, bool $shouldMatch): void
    {
        self::assertEquals($shouldMatch, (new ClassShortNameMatcher())->match($subject, $query));
    }

    /**
     * @return Generator<string, array{string,string,bool}>
     */
    public function provideMatch(): Generator
    {
        yield 'does not match empty strings' => [
            '',
            'foob',
            false
        ];

        yield 'does not match non-matching string' => [
            'barf',
            'foob',
            false
        ];

        yield 'matches if query is contained' => [
            'foob',
            'foobar',
            true
        ];

        yield 'matches short name of namespace' => [
            'Foo',
            'Barfoo\\Foobar',
            true
        ];

        yield 'matches short name of namespace only' => [
            'Barfoo',
            'Barfoo\\Foobar',
            false
        ];

        yield 'only matches prefix of short name' => [
            'bar',
            'Barfoo\\Foobar',
            false
        ];

        yield 'does not allow regex' => [
            '.oobar',
            'Barfoo\\Foobar',
            false
        ];
    }
}
