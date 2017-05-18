# SimpleDBAL
PHP 7.1+ PDO/Mysqli abstraction layer with a KISS approach.

Introduction
------------
PHP offers 2 different APIs to connect to a SQL database: `PDO` and `mysqli` (we won't talk about `mysql_*` functions since they're deprecated). Both do more or less the same thing, but:

* Their API are completely different (different method names and signatures)
* They both have their own pros and cons.
* Some features of one are missing in the other.

This library exposes an API that can be used by any of them, transparently. Which means you may change from `PDO` to `mysqli` and vice-versa without having to rewrite your whole code.

Additionnal features
--------------------
* Parameter binding on the fly for prepared statements
* `DateTimeInterface` objects automatic binding (formats to YYYY-MM-DD HH:ii:ss)
* Asynchronous queries (Promises)
* Parallel queries (only supported by the `mysqli` adapter - the `pdo` adapter continues to send queries one after the other)
* Named parameters for prepared statements in `mysqli` (portage with regexps - experimental feature)
* Can silently reconnect after a lost connection

The `Result` object
------------------
Every query sent to the adapter must return a `BenTools\SimpleDBAL\Contract\ResultInterface` object.

For _SELECT_ queries, use the following methods:
* `$result->asArray()` to fetch an array containing the whole resultset
* `$result->asRow()` to fetch the 1st row of the resultset, as an associative array
* `$result->asList()` to fetch the 1st column of the resultset, as an indexed array
* `$result->asValue()` to fetch a single value (i.e the 1st column of the 1st row)
* `foreach ($result as $row)` to iterate over the resultset (uses lazy-loading)
* `count($result)` to return the number of rows of the resultset.

For _INSERT_ / _UPDATE_ / _DELETE_ queries, use the following methods:
* `count($result)` to return the number of affected rows
* `$result->getLastInsertId()` to get the id of the last inserted row or sequence value.

Installation
------------
```
composer require bentools/simpledbal
```

Tests
-----
```
./vendor/bin/phpunit
```

Documentation
-----

[Getting started](doc/GettingStarted.md)

[Configuring auto-reconnect](doc/Configuration.md)

[Asynchronous and parallel queries](doc/AsynchronousQueries.md)

[Connection pools](doc/ConnectionPools.md)
