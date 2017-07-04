[![Latest Stable Version](https://poser.pugx.org/bentools/simple-dbal/v/stable)](https://packagist.org/packages/bentools/simple-dbal)
[![License](https://poser.pugx.org/bentools/simple-dbal/license)](https://packagist.org/packages/bentools/simple-dbal)
[![Quality Score](https://img.shields.io/scrutinizer/g/bpolaszek/simple-dbal.svg?style=flat-square)](https://scrutinizer-ci.com/g/bpolaszek/simple-dbal)
[![Total Downloads](https://poser.pugx.org/bentools/simple-dbal/downloads)](https://packagist.org/packages/bentools/simple-dbal)

# SimpleDBAL
PHP 7.1+ PDO/Mysqli abstraction layer with a KISS approach.

Introduction
------------
PHP offers 2 different APIs to connect to a SQL database: `PDO` and `mysqli` (we won't talk about `mysql_*` functions since they're deprecated). Both do more or less the same thing, but:

* Their API are completely different (different method names and signatures)
* They both have their own pros and cons.
* Some features of one are missing in the other.

This library exposes an API that can be used by any of them, transparently, in a more modern approach (OOP, iterators, return types, etc). 

This also means you can switch from `PDO` to `mysqli` and vice-versa without having to rewrite your whole code.

From my personnal experience, I was used to `PDO` and was forced to deal with `mysqli` in another project and it was really messy.

Overview
--------

```php
use BenTools\SimpleDBAL\Model\Credentials;
use BenTools\SimpleDBAL\Model\SimpleDBAL;

$credentials = new Credentials('localhost', 'user', 'password', 'database');
$cnx         = SimpleDBAL::factory($credentials, SimpleDBAL::PDO);
$query       = "SELECT `id`, `name` FROM guys WHERE created_at > ?";
foreach ($cnx->execute($query, [new DateTime('-1 month')]) as $item) {
    var_dump($item['name']);
}
```

Additionnal features
--------------------
* Parameter binding on the fly for prepared statements
* `DateTimeInterface` objects automatic binding (formats to YYYY-MM-DD HH:ii:ss)
* [Asynchronous queries](doc/03-AsynchronousQueries.md) (Promises)
* [Parallel queries](doc/03-AsynchronousQueries.md#parallel-queries)
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

[Getting started](doc/01-GettingStarted.md)

[Configuring auto-reconnect](doc/02-Configuration.md)

[Asynchronous and parallel queries](doc/03-AsynchronousQueries.md)

[Connection pools](doc/04-ConnectionPools.md)

[Known Issues](doc/05-KnownIssues.md)