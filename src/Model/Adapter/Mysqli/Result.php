<?php

namespace BenTools\SimpleDBAL\Model\Adapter\Mysqli;

use BenTools\SimpleDBAL\Contract\ResultInterface;
use BenTools\SimpleDBAL\Model\Exception\DBALException;
use IteratorAggregate;
use mysqli;
use mysqli_result;
use mysqli_stmt;

final class Result implements IteratorAggregate, ResultInterface
{
    /**
     * @var mysqli|null
     */
    private $mysqli;

    /**
     * @var mysqli_result|null
     */
    private $result;

    private $frozen = false;

    public function __construct(mysqli $mysqli = null, mysqli_result $result = null)
    {
        $this->mysqli = $mysqli;
        $this->result = $result;
    }

    /**
     * @inheritDoc
     */
    public function getLastInsertId()
    {
        if (null === $this->mysqli) {
            throw new DBALException("No \mysqli object provided.");
        }

        return $this->mysqli->insert_id;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return null === $this->result ? $this->getAffectedRows() : $this->getNumRows();
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        if (null === $this->result) {
            throw new DBALException("No \mysqli_result object provided.");
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
            throw new DBALException("No \mysqli_result object provided.");
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
            throw new DBALException("No \mysqli_result object provided.");
        }

        $this->freeze();

        $generator = function (mysqli_result $result) {
            while (null !== ($row = $result->fetch_array(MYSQLI_NUM))) {
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
            throw new DBALException("No \mysqli_result object provided.");
        }

        $this->freeze();

        $row = $this->result->fetch_array(MYSQLI_NUM);

        return $row[0] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        if (null === $this->result) {
            throw new DBALException("No \mysqli_result object provided.");
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

    private function getAffectedRows(): int
    {
        if (null === $this->mysqli) {
            throw new DBALException("No \mysqli object provided.");
        }

        return $this->mysqli->affected_rows;
    }

    private function getNumRows(): int
    {
        if (null === $this->result) {
            throw new DBALException("No \mysqli_result object provided.");
        }

        return $this->result->num_rows;
    }

    public static function from(...$arguments): self
    {
        $instance = new self();
        foreach ($arguments as $argument) {
            if ($argument instanceof mysqli) {
                $instance->mysqli = $argument;
            }
            if ($argument instanceof mysqli_result) {
                $instance->result = $argument;
            }
        }

        return $instance;
    }
}
