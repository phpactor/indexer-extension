Project Query
=============

[![Build Status](https://travis-ci.org/phpactor/workspace-query.svg?branch=master)](https://travis-ci.org/phpactor/workspace-query)

[Phpactor](https://github.com/phpactor/phpactor) extension for indexing querying a project's workspace.

Phpactor tries to not use an index but in some cases it is unavoidable - for
example when trying to find implementations of a given interface.

This package currently provides:

- Goto Implementation Implementation
- [Worse Reflection](https://github.com/phpactor/worse-reflection) source code locator.

Installation
------------

From the CLI:

```
$ phpactor extension:install 
$ phpactor extension:install "phpactor/workspace-query"
```

From VIM:

```
:call phpactor#ExtensionInstall('phpactor/workspace-query')
```

Usage
-----

```
$ phpactor index:build --watch
$ phpactor index:query:class "My\\Class\\Name"
```

Note that this can take a _long_ time. Refreshing the index should generally
take less than a second however.

In VIM the index will automatically be used for:

- Goto Implementation(s).
- Goto Definition.
- Generally any other operation that requires a class name look-up.

TODO:

- [ ] Function indexing. 
