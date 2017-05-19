<?php

namespace BenTools\SimpleDBAL\Model\Adapter\Mysqli;

use BenTools\SimpleDBAL\Contract\AdapterInterface;
use BenTools\SimpleDBAL\Contract\CredentialsInterface;
use BenTools\SimpleDBAL\Contract\ReconnectableAdapterInterface;
use BenTools\SimpleDBAL\Contract\ResultInterface;
use BenTools\SimpleDBAL\Contract\StatementInterface;
use BenTools\SimpleDBAL\Contract\TransactionAdapterInterface;
use BenTools\SimpleDBAL\Model\ConfigurableTrait;
use BenTools\SimpleDBAL\Model\Exception\AccessDeniedException;
use BenTools\SimpleDBAL\Model\Exception\DBALException;
use BenTools\SimpleDBAL\Model\Exception\MaxConnectAttempsException;
use BenTools\SimpleDBAL\Model\Exception\ParamBindingException;
use GuzzleHttp\Promise\PromiseInterface;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use Throwable;

class MysqliAdapter implements AdapterInterface, TransactionAdapterInterface, ReconnectableAdapterInterface
{

    use ConfigurableTrait;

    const OPT_RESOLVE_NAMED_PARAMS   = 'resolve_named_params';

    /**
     * @var mysqli
     */
    private $cnx;

    /**
     * @var CredentialsInterface
     */
    private $credentials;

    /**
     * @var int
     */
    private $reconnectAttempts = 0;

    /**
     * MysqliAdapter constructor.
     * @param mysqli $cnx
     * @param CredentialsInterface $credentials
     * @param array|null $options
     */
    public function __construct(mysqli $cnx, CredentialsInterface $credentials, array $options = null)
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->cnx         = $cnx;
        $this->credentials = $credentials;
        $this->options     = $options;
    }

    /**
     * @inheritDoc
     */
    public function getWrappedConnection()
    {
        return $this->cnx;
    }

    /**
     * @inheritDoc
     */
    public function getCredentials(): CredentialsInterface
    {
        return $this->credentials;
    }

    /**
     * @inheritDoc
     */
    public function isConnected(): bool
    {
        return false !== $this->cnx->stat();
    }

    /**
     * @inheritDoc
     */
    public function shouldReconnect(): bool
    {
        return $this->reconnectAttempts < (int) $this->getOption(self::OPT_MAX_RECONNECT_ATTEMPTS);
    }

    private function reconnect()
    {
        if (0 === (int) $this->getOption(self::OPT_MAX_RECONNECT_ATTEMPTS)) {
            throw new MaxConnectAttempsException("Connection lost.");
        } elseif ($this->reconnectAttempts === (int) $this->getOption(self::OPT_MAX_RECONNECT_ATTEMPTS)) {
            throw new MaxConnectAttempsException("Max attempts to connect to database has been reached.");
        }
        try {
            $this->cnx = self::createLink($this->getCredentials());
            if ($this->isConnected()) {
                $this->reconnectAttempts = 0;
            } else {
                $this->reconnect();
            }
        } catch (Throwable $e) {
            $this->reconnectAttempts++;
        }
    }

    /**
     * @return bool
     */
    public function shouldResolveNamedParameters(): bool
    {
        return (bool) $this->getOption(self::OPT_RESOLVE_NAMED_PARAMS);
    }

    /**
     * @param string $queryString
     * @return bool
     */
    protected function hasNamedParameters(string $queryString): bool
    {
        return preg_match('#:([a-zA-Z0-9_]+)#', $queryString);
    }

    /**
     * @inheritDoc
     */
    public function prepare(string $queryString, array $values = null): StatementInterface
    {
        if ($this->shouldResolveNamedParameters() && $this->hasNamedParameters($queryString)) {
            $runnableQueryString = $this->convertToRunnableQuery($queryString);
        } else {
            $runnableQueryString = &$queryString;
        }
        try {
            $wrappedStmt = self::wrapWithErrorHandler(function () use ($runnableQueryString) {
                return $this->cnx->prepare($runnableQueryString);
            });
        } catch (mysqli_sql_exception $e) {
            if (!$this->isConnected()) {
                $this->reconnect();
                return $this->prepare($queryString, $values);
            }
            throw new DBALException($e->getMessage(), (int) $e->getCode(), $e);
        }
        return new Statement($this, $wrappedStmt, $values, $queryString, $runnableQueryString);
    }

    /**
     * @param Statement|string $stmt
     * @param array|null $values
     * @return Result
     */
    public function execute($stmt, array $values = null): ResultInterface
    {
        if (is_string($stmt)) {
            $stmt = $this->prepare($stmt, $values);
        } else {
            if (!$stmt instanceof Statement) {
                throw new \InvalidArgumentException(sprintf('Expected %s object, got %s', Statement::class, get_class($stmt)));
            }
            if (null !== $values) {
                $stmt = $stmt->withValues($values);
            }
        }

        try {
            $this->runStmt($stmt);
            $result = $stmt->createResult();
        } catch (Throwable $e) {
            if (!$this->isConnected()) {
                $this->reconnect();
                return $this->execute($this->prepare((string) $stmt, $stmt->getValues()));
            }
            throw $e;
        }
        return $result;
    }

    /**
     * EXPERIMENTAL ! Executes a statement asynchronously.
     * The promise will return a Result object.
     *
     * @param $stmt
     * @param array|null $values
     * @return PromiseInterface
     */
    public function executeAsync($stmt, array $values = null): PromiseInterface
    {
        if (is_string($stmt)) {
            $stmt = $this->prepare($stmt, $values);
        } else {
            if (!$stmt instanceof Statement) {
                throw new \InvalidArgumentException(sprintf('Expected %s object, got %s', Statement::class, get_class($stmt)));
            }
            if (null !== $values) {
                $stmt = $stmt->withValues($values);
            }
        }

        // Simulate query string (Mysqli Asynchronous queries do not support prepared statements)
        $simulatedQueryString = $stmt->preview();

        // Clone connection if necessary (Mysqli Asynchronous queries require a different connection to work properly)
        $credentials = $this->getCredentials();
        try {
            $cnx = new mysqli($credentials->getHostname(), $credentials->getUser(), $credentials->getPassword(), $credentials->getDatabase(), $credentials->getPort());
        } catch (mysqli_sql_exception $e) {
            throw new AccessDeniedException($e->getMessage(), (int) $e->getCode(), $e);
        }
        $promise = MysqliAsync::query($simulatedQueryString, $cnx)->then(function ($result) use ($cnx, $stmt) {
            if (!$result instanceof mysqli_result) {
                $result = null;
            }
            return new Result($cnx, $result);
        });
        return $promise;
    }

    private function runStmt(Statement $stmt)
    {
        $wrappedStmt = $stmt->getWrappedStatement();
        try {
            self::wrapWithErrorHandler(function () use ($stmt, $wrappedStmt) {
                $stmt->bind();
                $wrappedStmt->execute();
            });
        } catch (mysqli_sql_exception $e) {
            if (false !== strpos($e->getMessage(), 'No data supplied for parameters in prepared statement')) {
                throw new ParamBindingException($e->getMessage(), (int) $e->getCode(), $e, $stmt);
            } elseif (false !== strpos($e->getMessage(), "Number of variables doesn't match number of parameters in prepared statement")) {
                throw new ParamBindingException($e->getMessage(), (int) $e->getCode(), $e, $stmt);
            } else {
                throw new DBALException($e->getMessage(), (int) $e->getCode(), $e);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction()
    {
        $this->getWrappedConnection()->autocommit(false);
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        $this->getWrappedConnection()->commit();
        $this->getWrappedConnection()->autocommit(true);
    }

    /**
     * @inheritDoc
     */
    public function rollback()
    {
        $this->getWrappedConnection()->rollback();
        $this->getWrappedConnection()->autocommit(true);
    }

    /**
     * Convert a query with named parameters (not natively supported by mysqli)
     *
     * @param string $queryString
     * @return string
     */
    private function convertToRunnableQuery(string $queryString): string
    {
        return preg_replace('#:([a-zA-Z0-9_]+)#', '?', $queryString);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultOptions(): array
    {
        return [
            self::OPT_MAX_RECONNECT_ATTEMPTS => self::DEFAULT_MAX_RECONNECT_ATTEMPTS,
            self::OPT_USLEEP_AFTER_FIRST_ATTEMPT => self::DEFAULT_USLEEP_AFTER_FIRST_ATTEMPT,
            self::OPT_RESOLVE_NAMED_PARAMS   => true,
        ];
    }

    /**
     * @param CredentialsInterface $credentials
     * @param bool $resolveNamedParameters
     * @return MysqliAdapter
     */
    public static function factory(CredentialsInterface $credentials, array $options = null): self
    {
        return new static(self::createLink($credentials), $credentials, $options);
    }

    /**
     * @param CredentialsInterface $credentials
     * @return mysqli
     */
    private static function createLink(CredentialsInterface $credentials): mysqli
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            return new mysqli($credentials->getHostname(), $credentials->getUser(), $credentials->getPassword(), $credentials->getDatabase(), $credentials->getPort());
        } catch (mysqli_sql_exception $e) {
            throw new AccessDeniedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param callable $run
     * @return mixed|void
     */
    private static function wrapWithErrorHandler(callable $run)
    {
        $errorHandler = function ($errno, $errstr) {
            throw new mysqli_sql_exception($errstr, $errno);
        };
        set_error_handler($errorHandler, E_WARNING);
        $result = $run();
        restore_error_handler();
        return $result;
    }
}
