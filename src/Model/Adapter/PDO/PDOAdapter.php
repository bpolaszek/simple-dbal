<?php

namespace BenTools\SimpleDBAL\Model\Adapter\PDO;

use BenTools\SimpleDBAL\Contract\AdapterInterface;
use BenTools\SimpleDBAL\Contract\CredentialsInterface;
use BenTools\SimpleDBAL\Contract\ReconnectableAdapterInterface;
use BenTools\SimpleDBAL\Contract\StatementInterface;
use BenTools\SimpleDBAL\Contract\ResultInterface;
use BenTools\SimpleDBAL\Contract\TransactionAdapterInterface;
use BenTools\SimpleDBAL\Model\ConfigurableTrait;
use BenTools\SimpleDBAL\Model\Exception\AccessDeniedException;
use BenTools\SimpleDBAL\Model\Exception\DBALException;
use BenTools\SimpleDBAL\Model\Exception\MaxConnectAttempsException;
use BenTools\SimpleDBAL\Model\Exception\ParamBindingException;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use PDO;
use PDOException;
use Throwable;

class PDOAdapter implements AdapterInterface, TransactionAdapterInterface, ReconnectableAdapterInterface
{
    use ConfigurableTrait;

    /**
     * @var PDO
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
     * PDOAdapter constructor.
     * @param PDO $cnx
     * @param CredentialsInterface|null $credentials
     * @param array|null $options
     */
    protected function __construct(PDO $cnx, CredentialsInterface $credentials = null, array $options = null)
    {
        $this->cnx = $cnx;
        if (PDO::ERRMODE_EXCEPTION !== $this->cnx->getAttribute(PDO::ATTR_ERRMODE)) {
            $this->cnx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        $this->credentials = $credentials;
        if (null !== $options) {
            $this->options     = array_replace($this->getDefaultOptions(), $options);
        }
    }

    /**
     * @inheritDoc
     */
    public function getWrappedConnection(): PDO
    {
        return $this->cnx;
    }

    /**
     * @inheritDoc
     */
    public function getCredentials(): ?CredentialsInterface
    {
        return $this->credentials;
    }

    /**
     * @inheritDoc
     */
    public function isConnected(): bool
    {
        try {
            self::wrapWithErrorHandler(function () {
                $this->cnx->getAttribute(PDO::ATTR_SERVER_INFO);
            });
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function shouldReconnect(): bool
    {
        return !$this->isConnected() && $this->reconnectAttempts < (int) $this->getOption(self::OPT_MAX_RECONNECT_ATTEMPTS);
    }

    /**
     * Tries to reconnect to database.
     */
    private function reconnect()
    {
        if (0 === (int) $this->getOption(self::OPT_MAX_RECONNECT_ATTEMPTS)) {
            throw new MaxConnectAttempsException("Connection lost.");
        } elseif ($this->reconnectAttempts === (int) $this->getOption(self::OPT_MAX_RECONNECT_ATTEMPTS)) {
            throw new MaxConnectAttempsException("Max attempts to connect to database has been reached.");
        }

        if (null === $this->credentials) {
            throw new AccessDeniedException("Unable to reconnect: credentials not provided.");
        }

        try {
            if (0 !== $this->reconnectAttempts) {
                usleep((int) $this->getOption(self::OPT_USLEEP_AFTER_FIRST_ATTEMPT));
            }
            $this->cnx = self::createLink($this->getCredentials(), $this->options);
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
     * @inheritDoc
     */
    public function prepare(string $query, array $values = null): StatementInterface
    {
        try {
            $wrappedStmt = $this->cnx->prepare($query);
        } catch (PDOException $e) {
            if (!$this->isConnected()) {
                $this->reconnect();
                return $this->prepare($query, $values);
            }
            throw new DBALException($e->getMessage(), (int) $e->getCode(), $e);
        }
        return new Statement($this, $wrappedStmt, $values);
    }

    /**
     * @inheritDoc
     */
    public function execute($stmt, array $values = null): ResultInterface
    {
        if (is_string($stmt)) {
            $stmt = $this->prepare($stmt);
        }
        if (!$stmt instanceof Statement) {
            throw new \InvalidArgumentException(sprintf('Expected %s object, got %s', Statement::class, get_class($stmt)));
        }
        if (null !== $values) {
            $stmt = $stmt->withValues($values);
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
     * @inheritDoc
     */
    public function executeAsync($stmt, array $values = null): PromiseInterface
    {
        $promise = new Promise(function () use (&$promise, $stmt, $values) {
            try {
                $promise->resolve($this->execute($stmt, $values));
            } catch (DBALException $e) {
                $promise->reject($e);
            }
        });
        return $promise;
    }

    /**
     * @param \PDOStatement $wrappedStmt
     */
    private function runStmt(Statement $stmt)
    {
        $wrappedStmt = $stmt->getWrappedStatement();
        try {
            self::wrapWithErrorHandler(function () use ($stmt, $wrappedStmt) {
                $stmt->bind();
                $wrappedStmt->execute();
            });
        } catch (\PDOException $e) {
            if (false !== strpos($e->getMessage(), 'no parameters were bound')) {
                throw new ParamBindingException($e->getMessage(), (int) $e->getCode(), $e, $stmt);
            }
            if (false !== strpos($e->getMessage(), 'number of bound variables does not match number')) {
                throw new ParamBindingException($e->getMessage(), (int) $e->getCode(), $e, $stmt);
            }
            throw new DBALException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction(): void
    {
        $this->getWrappedConnection()->beginTransaction();
    }

    /**
     * @inheritDoc
     */
    public function commit(): void
    {
        $this->getWrappedConnection()->commit();
    }

    /**
     * @inheritDoc
     */
    public function rollback(): void
    {
        $this->getWrappedConnection()->rollBack();
    }

    /**
     * @inheritDoc
     */
    public function getDefaultOptions(): array
    {
        return [
            self::OPT_MAX_RECONNECT_ATTEMPTS => self::DEFAULT_MAX_RECONNECT_ATTEMPTS,
            self::OPT_USLEEP_AFTER_FIRST_ATTEMPT => self::DEFAULT_USLEEP_AFTER_FIRST_ATTEMPT,
        ];
    }

    /**
     * @param CredentialsInterface $credentials
     * @return PDOAdapter
     */
    public static function factory(CredentialsInterface $credentials, array $options = null): self
    {
        return new static(self::createLink($credentials, $options), $credentials, $options);
    }

    /**
     * @param PDO                       $link
     * @param CredentialsInterface|null $credentials
     * @return PDOAdapter
     */
    public static function createFromLink(PDO $link, CredentialsInterface $credentials = null): self
    {
        return new static($link, $credentials);
    }

    /**
     * @param CredentialsInterface $credentials
     * @return PDO
     */
    private static function createLink(CredentialsInterface $credentials, array $options = null): PDO
    {
        $dsn = sprintf('%s:', $credentials->getPlatform());
        $dsn .= sprintf('host=%s;', $credentials->getHostname());
        if (null !== $credentials->getPort()) {
            $dsn .= sprintf('port=%s;', $credentials->getPort());
        }
        if (null !== $credentials->getDatabase()) {
            $dsn .= sprintf('dbname=%s;', $credentials->getDatabase());
        }
        try {
            $pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            if (isset($options['charset'])) {
                $pdoOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = sprintf('SET NAMES %s', $options['charset']);
            }
            return new PDO($dsn, $credentials->getUser(), $credentials->getPassword(), $pdoOptions);
        } catch (\PDOException $e) {
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
            throw new PDOException($errstr, $errno);
        };
        set_error_handler($errorHandler, E_WARNING);
        $result = $run();
        restore_error_handler();
        return $result;
    }
}
