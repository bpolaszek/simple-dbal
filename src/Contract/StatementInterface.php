<?php

namespace BenTools\SimpleDBAL\Contract;

interface StatementInterface
{

    /**
     * The database connection.
     *
     * @return AdapterInterface
     */
    public function getConnection(): AdapterInterface;

    /**
     * The original query string.
     *
     * @return string
     */
    public function getQueryString(): string;

    /**
     * The statement object (PDOStatement, mysqli_stmt)
     * @return object
     */
    public function getWrappedStatement();

    /**
     * The values to bind.
     *
     * @return array
     */
    public function getValues(): ?array;

    /**
     * Return a cloned statement object with different values.
     *
     * @param array $values
     */
    public function withValues(array $values = null): StatementInterface;

    /**
     * Bind values to the wrapped statement
     */
    public function bind(): void;

    /**
     * Show query string with bound values.
     *
     * @return string
     */
    public function preview(): string;

    /**
     * Result factory.
     *
     * @return ResultInterface
     */
    public function createResult(): ResultInterface;

    /**
     * The original query string.
     *
     * @return string
     */
    public function __toString(): string;
}
