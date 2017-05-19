# Asynchronous Queries

SimpleDBAL ships with support of asynchronous queries, with the help of [Guzzle Promises](https://github.com/guzzle/promises).

You can run queries in an asynchronous way and handle their errors like this:
```php
require_once __DIR__ . '/vendor/autoload.php';
use BenTools\SimpleDBAL\Contract\ResultInterface;
use BenTools\SimpleDBAL\Model\Credentials;
use BenTools\SimpleDBAL\Model\SimpleDBAL;

$credentials = new Credentials('localhost', "user", 'password', 'database');
$cnx = SimpleDBAL::factory($credentials);
$promise = $cnx->executeAsync("SELECT * FROM my_table")
    ->then(function (ResultInterface $result) {
    foreach ($result as $item) {
        var_dump($item);
    }
})
    ->otherwise(function (Throwable $e) {
    var_dump($e->getMessage());
});

$promise->wait();
```

# Parallel queries

By default, asynchronous queries are executed sequentially, in the order you call the `wait()` functions.

**SimpleDBAL** allows you to run multiple queries at once and handling their results as soon as they are available.

```php
require_once __DIR__ . '/vendor/autoload.php';
use BenTools\SimpleDBAL\Model\Adapter\Mysqli\MysqliAdapter;
use BenTools\SimpleDBAL\Model\Credentials;
use BenTools\SimpleDBAL\Model\SimpleDBAL;
use function GuzzleHttp\Promise\all;

$credentials = new Credentials('localhost', "user", 'password', 'database');
$cnx = SimpleDBAL::factory($credentials, SimpleDBAL::MYSQLI);
$cnx->setOption(MysqliAdapter::OPT_ENABLE_PARALLEL_QUERIES, true);
$start = microtime(true);
$promises = [
    $cnx->executeAsync("SELECT SLEEP(10);"),
    $cnx->executeAsync("SELECT SLEEP(8);"),
];
all($promises)->wait();
$end = microtime(true);
var_dump(round($end - $start, 3)); // 10.072
```

Things you have to know about parallel queries:

* Only the `mysqli` driver supports parallel queries. If you use SimpleDBAL with PDO, queries will just continue to be sent sequentially.
* The `mysqli` driver does not support prepared statements when sending parallel queries. SimpleDBAL then emulates them, with the use of regexp, so their support is really experimental.
* Sending multiple queries at once requires cloning the connection for each query. This has several drawbacks:
    * Make sure your MySQL server can handle as many connections.
    * Cloning the connection has a performance cost, due to latency and memory. Make sure to use parallel queries only with slow queries, otherwise you probably won't see any performance benefit.
* Parallel queries are disabled by default. You need to set the `MysqliAdapter::OPT_ENABLE_PARALLEL_QUERIES` option to `true`    

Previous: [Configuration](02-Configuration.md)

Next: [Connection pools](04-ConnectionPools.md)