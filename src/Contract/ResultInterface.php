<?php

namespace BenTools\SimpleDBAL\Contract;

use Countable;
use Traversable;

interface ResultInterface extends Countable, Traversable
{
    /**
     * Return the whole resultset.
     *
     * @return array
     */
    public function asArray(): array;

    /**
     * Return the first result of the resultset.
     *
     * @return array|null
     */
    public function asRow(): ?array;

    /**
     * Return an indexed array of items.
     *
     * @return array
     */
    public function asList(): array;

    /**
     * Return a single, scalar value, or null.
     *
     * @return mixed|null
     */
    public function asValue();

    /**
     * Return the ID of the last inserted row or sequence value.
     *
     * @return mixed|null
     */
    public function getLastInsertId();

    /**
     * Return the number of affected rows.
     *
     * @return int
     */
    public function count(): int;
}
