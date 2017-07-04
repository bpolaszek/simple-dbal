<?php

namespace BenTools\SimpleDBAL\Model\Adapter\Mysqli;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;

final class MysqliAsync implements \Countable
{
    private $queries   = [];
    private $processed = 0;

    private static $instance;
    private static $sleep = 0;
    private static $usleep = 50000;

    /**
     * MysqliAsync constructor.
     * Disabled to force singleton use.
     */
    private function __construct()
    {
    }

    /**
     * @param string $query
     * @param mysqli $cnx
     * @return Promise
     */
    public static function query(string $query, mysqli $cnx)
    {
        $promise = new Promise(function () {
            self::getInstance()->wait();
        });
        $cnx->query($query, MYSQLI_ASYNC);
        self::getInstance()->queries[] = [
            'l' => $cnx,
            'p' => $promise,
        ];
        return $promise;
    }

    /**
     * @return MysqliAsync
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Poll every $wait seconds.
     * To poll every 100ms, call pollEvery(0.100).
     *
     * @param float $wait
     */
    public static function pollEvery(float $wait)
    {
        if (false === strpos((string) $wait, '.')) {
            self::$sleep = (int) $wait;
            self::$usleep = 0;
        } else {
            list($seconds, $hundreds) = explode('.', (string) $wait);
            self::$sleep = (int) $seconds;
            self::$usleep = (int) (((float) sprintf('0.%d', $hundreds)) * 1000000);
        }
    }

    /**
     * @param array $query
     * @return mysqli
     */
    private function getConnection(array $query): mysqli
    {
        return $query['l'];
    }

    /**
     * @param array $query
     * @return PromiseInterface
     */
    private function getPromise(array $query): PromiseInterface
    {
        return $query['p'];
    }

    /**
     * Resets current pool.
     */
    private function reset()
    {
        $this->queries   = [];
        $this->processed = 0;
    }

    /**
     * Wait for pending queries to complete.
     */
    private function wait()
    {
        do {
            if (empty($this->queries)) {
                break;
            }
            $links = $errors = $reject = [];
            foreach ($this->queries as $link) {
                $links[] = $errors[] = $reject[] = $link['l'];
            }
            if (!mysqli_poll($links, $errors, $reject, self::$sleep, self::$usleep)) {
                continue;
            }
            foreach ($this->queries as $l => $link) {
                $promise = $this->getPromise($link);
                $cnx    = $this->getConnection($link);
                try {
                    $result = $cnx->reap_async_query();
                    if ($result instanceof mysqli_result) {
                        $promise->resolve($result);
                    } else {
                        $errNo = mysqli_errno($cnx);
                        $errStr = mysqli_error($cnx);
                        if (0 !== $errNo || 0 !== strlen($errStr)) {
                            throw new mysqli_sql_exception($errStr, $errNo);
                        } else {
                            $promise->resolve($cnx);
                        }
                    }
                } catch (mysqli_sql_exception $e) {
                    $promise->reject($e);
                }
                $this->processed++;
                unset($this->queries[$l]);
            }
        } while ($this->processed < count($this->queries));
        $this->reset();
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->queries);
    }
}
