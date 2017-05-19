# Getting started

Insert operations
-----------------

```php
require_once __DIR__ . '/vendor/autoload.php';

use BenTools\SimpleDBAL\Model\Credentials;
use BenTools\SimpleDBAL\Model\SimpleDBAL;

// Connect
$credentials = new Credentials('localhost', 'user', 'password', 'database');
$cnx = SimpleDBAL::factory($credentials, SimpleDBAL::PDO); // Can also be SimpleDBAL::MYSQLI

// Create sample table
$cnx->execute("CREATE TABLE `test` (`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(255) NOT NULL, `created_at` datetime DEFAULT NULL, PRIMARY KEY (`id`));");

// Insert sample data
$insert = $cnx->prepare("INSERT INTO test (`id`, `name`, `created_at`) VALUES (:id, :name, :created_at)");
$result = $cnx->execute($insert, [
    'id' => null,
    'name' => 'John',
    'created_at' => new DateTime(),
]);
var_dump($result->getLastInsertId()); // 1
$result = $cnx->execute($insert, [
    'id' => null,
    'name' => 'Bob',
    'created_at' => new DateTime(),
]);
var_dump($result->getLastInsertId()); // 2
```

Select operations
-----------------

Now we have in table `test`:

| id | name | created_at          |
|----|------|---------------------|
| 1  | John | 2017-05-14 16:43:05 |
| 2  | Bob  | 2017-05-14 16:43:05 |


**Iterate over the resultset:**

```php
$result = $cnx->execute("SELECT `id`, `name` FROM test");
foreach ($result as $item) {
    var_export($item);
}
```

```php
array (
  'id' => '1',
  'name' => 'John',
)
array (
  'id' => '2',
  'name' => 'Bob',
)
```

**Retrieve the whole resultset:**

```php
$result = $cnx->execute("SELECT * FROM test WHERE created_at > ?", [new DateTime('today midnight')]);
var_export($result->asArray());
```

```php
array (
  0 => 
  array (
    'id' => '1',
    'name' => 'John',
    'created_at' => '2017-05-14 16:43:05',
  ),
  1 => 
  array (
    'id' => '2',
    'name' => 'Bob',
    'created_at' => '2017-05-14 16:43:05',
  ),
)
```

**Retrieve the 1st row:**

```php
var_export($result->asRow());
```

```php
array (
  'id' => '1',
  'name' => 'John',
  'created_at' => '2017-05-14 16:43:05',
)
```

**Retrieve the resultset as a list:**

```php
$result = $cnx->execute("SELECT `name` FROM test");
var_export($result->asList());
```

```php
array (
  0 => 'John',
  1 => 'Bob',
)
```

**Retrieve a single value:**
```php
$result = $cnx->execute("SELECT `name` FROM test WHERE id = :id", ['id' => 1]);
var_dump($result->asValue());
```

```php
string(4) "John"
```

Next: [Configuration](02-Configuration.md)