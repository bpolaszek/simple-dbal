<?php

namespace BenTools\SimpleDBAL\Model\Adapter\Mysqli;

use BenTools\SimpleDBAL\Contract\ResultInterface;
use BenTools\SimpleDBAL\Model\Exception\DBALException;
use IteratorAggregate;
use mysqli;
use mysqli_result;
use mysqli_stmt;

class Result implements IteratorAggregate, ResultInterface
{
    /**
     * @var mysqli
     */
    private $mysqli;

    /**
     * @var mysqli_stmt
     */
    private $stmt;

    /**
     * @var mysqli_result
     */
    private $result;

    private $frozen = false;

    /**
     * Result constructor.
     * @param mysqli $mysqli
     * @param mysqli_stmt $stmt
     * @param mysqli_result $result
     */
    public function __construct(mysqli $mysqli, mysqli_result $result = null, mysqli_stmt $stmt = null)
    {
        $this->mysqli = $mysqli;
        $this->stmt = $stmt;
        $this->result = $result;
    }

    /**
     * @inheritDoc
     */
    public function getLastInsertId()
    {
        return $this->mysqli->insert_id;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return null === $this->result ? $this->mysqli->affected_rows : $this->result->num_rows;
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        if (null === $this->result) {
            throw new DBALException("No mysqli_result object provided.");
        }

        $this->freeze();

        return $this->result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * @inheritDoc
     */
    public function asRow(): ?array
    {
        if (null === $this->result) {
            throw new DBALException("No mysqli_result object provided.");
        }

        $this->freeze();

        return $this->result->fetch_array(MYSQLI_ASSOC) ?: null;
    }

    /**
     * @inheritDoc
     */
    public function asList(): array
    {
        if (null === $this->result) {
            throw new DBALException("No mysqli_result object provided.");
        }

        $this->freeze();

        $generator = function (mysqli_result $result) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                yield $row[0];
            }
        };

        return iterator_to_array($generator($this->result));
    }

    /**
     * @inheritDoc
     */
    public function asValue()
    {
        if (null === $this->result) {
            throw new DBALException("No mysqli_result object provided.");
        }

        $this->freeze();

        $row = $this->result->fetch_array(MYSQLI_NUM);

        return $row ? $row[0] : null;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        if (null === $this->result) {
            throw new DBALException("No mysqli_result object provided.");
        }

        $this->freeze();

        while ($row = $this->result->fetch_array(MYSQLI_ASSOC)) {
            yield $row;
        }
    }

    private function freeze(): void
    {
        if (true === $this->frozen) {
            throw new DBALException("This result is frozen. You have to re-execute this statement.");
        }

        $this->frozen = true;
    }
}
