<?php

namespace BenTools\SimpleDBAL\Contract;

interface TransactionAdapterInterface
{
    /**
     * Init a transaction.
     */
    public function beginTransaction();

    /**
     * Commit pending queries.
     */
    public function commit();

    /**
     * Rollback transaction.
     */
    public function rollback();
}
