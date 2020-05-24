<?php

namespace Phpactor\Indexer\Model\Matcher;

use Phpactor\Indexer\Model\Matcher;

class ClassShortNameMatcher implements Matcher
{
    public function match(string $subject, string $query): bool
    {
        if ('' === $query) {
            return false;
        }

        if (0 === substr_count(ltrim($subject, '\\'), '\\')) {
            return strpos($subject, $query) === 0;
        }

        $pattern = '{\\\\' . preg_quote($query) . '[^\\\]*$}';

        return (bool)preg_match($pattern, $subject);
    }
}
