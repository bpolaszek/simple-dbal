<?php

namespace BenTools\SimpleDBAL\Contract;

use mysqli;
use PDO;

interface AdapterInterface extends ConnectionInterface
{
    /**
     * Return the wrapped connection object.
     *
     * @return mysqli|PDO
     */
    public function getWrappedConnection();

    /**
     * Return the credentials used to connect.
     *
     * @return CredentialsInterface
     */
    public function getCredentials(): ?CredentialsInterface;

    /**
     * Init a transaction.
     */
    public function beginTransaction(): void;

    /**
     * Commit current transaction
     */
    public function commit(): void;

    /**
     * Rollback current transaction
     */
    public function rollback(): void;
}
