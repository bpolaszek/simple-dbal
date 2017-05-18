<?php

namespace BenTools\SimpleDBAL\Contract;

use GuzzleHttp\Promise\PromiseInterface;

interface ConnectionInterface
{
    /**
     * Create a read statement.
     *
     * @param string $query
     * @param array $values
     * @return StatementInterface
     */
    public function prepare(string $query, array $values = null): StatementInterface;

    /**
     * Executes a read statement.
     *
     * @param StatementInterface|string $stmt
     * @return ResultInterface
     */
    public function execute($stmt, array $values = null): ResultInterface;

    /**
     * Executes a read statement asynchronously.
     * The promise MUST return a Result object.
     *
     * @param $stmt
     * @param array|null $values
     * @return PromiseInterface
     */
    public function executeAsync($stmt, array $values = null): PromiseInterface;
}
