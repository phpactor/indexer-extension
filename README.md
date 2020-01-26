Project Query
=============

[![Build Status](https://travis-ci.org/phpactor/project-query.svg?branch=master)](https://travis-ci.org/phpactor/project-query)

[Phpactor](https://github.com/phpactor/phpactor) Library for indexing querying a project's workspace.

Phpactor tries to not use an index but in some cases it is unavoidable - for
example when trying to find implementations of a given interface.

This library exists to provide a light-weight index and querying capability.

Usage
-----

It is not recommended to use this package independently, but if you're curious:

```php
<?php

use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\ProjectQuery\Adapter\Php\Serialized\FileRepository;
use Phpactor\ProjectQuery\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\ProjectQuery\Adapter\Worse\WorseIndexBuilder;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\ReflectorBuilder;
use Psr\Log\NullLogger;

$repository = new FileRepository(__DIR__ . '/path/to/cache');
$index = new SerializedIndex($repository);

$indexBuilder = new WorseIndexBuilder(
    $index,
    new SimpleFilesystem($this->workspace()->path('/project')), // better to use composer filesystem
    ReflectorBuilder::create()->addLocator(
       // source locator here
    )->build(),
    new NullLogger()
);

$indexBuilder->refresh();

$implementations = $index->implementations(FullyQualifiedName::fromString('Exception'));
```
