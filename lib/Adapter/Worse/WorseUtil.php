<?php

namespace Phpactor\WorkspaceQuery\Adapter\Worse;

use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use RuntimeException;

final class WorseUtil
{
    public static function classType(ReflectionClassLike $classLike): string
    {
        if ($classLike->isClass()) {
            return 'class';
        }

        if ($classLike->isInterface()) {
            return 'interface';
        }

        if ($classLike->isTrait()) {
            return 'trait';
        }

        throw new RuntimeException(
            'Could not determine type of reflection class - should never happen'
        );
    }
}
