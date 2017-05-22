# Configuration

Automatic reconnection
----------------------
**SimpleDBAL** can silently reconnect after a lost connection. To enable this feature, specify the number of attempts before throwing an exception:
```php
use BenTools\SimpleDBAL\Contract\ReconnectableAdapterInterface;
$adapter->setOption(ReconnectableAdapterInterface::OPT_MAX_RECONNECT_ATTEMPTS, 3); // Will try to reconnect 3 times before throwing an exception
```

Delay before reconnection
-------------------------
When enabling automatic reconnection, **SimpleDBAL** will immediately try to reconnect once.

If this attempts fails, each further attempt may be delayed by setting an usleep() parameter:
```php
use BenTools\SimpleDBAL\Contract\ReconnectableAdapterInterface;
$adapter->setOption(ReconnectableAdapterInterface::OPT_USLEEP_AFTER_FIRST_ATTEMPT, 100000); // Will wait 100 ms before furher retries
```


Previous: [Getting started](01-GettingStarted.md)

Next: [Asynchronous Queries](03-AsynchronousQueries.md)