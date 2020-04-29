Project Indexer
===============

[![Build Status](https://travis-ci.org/phpactor/indexer-extension.svg?branch=master)](https://travis-ci.org/phpactor/indexer-extension)

[Phpactor](https://github.com/phpactor/phpactor) extension for indexing querying a project's workspace.

Provides indexing and index-querying capabiltities for Phpactor.

Usage
-----

From CLI:

```
$ phpactor index:build --watch
$ phpactor index:query:class "My\\Class\\Name"
```

Integrations
------------

Various integrations are provided with other Phpactor components, e.g. source location for worse reflection, goto implementation implementations etc.
