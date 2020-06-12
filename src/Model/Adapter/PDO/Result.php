<?php

namespace BenTools\SimpleDBAL\Model\Adapter\PDO;

use BenTools\SimpleDBAL\Contract\ResultInterface;
use BenTools\SimpleDBAL\Model\Exception\DBALException;
use IteratorAggregate;
use PDO;
use PDOStatement;

final class Result implements IteratorAggregate, ResultInterface
{
    private $pdo;
    private $stmt;
    private $frozen = false;

    public function __construct(PDO $pdo = null, PDOStatement $stmt = null)
    {
        $this->pdo = $pdo;
        $this->stmt = $stmt;
    }

    /**
     * @inheritDoc
     */
    public function getLastInsertId()
    {
        if (null === $this->pdo) {
            throw new DBALException("No \PDO object provided.");
        }

        return $this->pdo->lastInsertId();
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        if (null === $this->stmt) {
            throw new DBALException("No \PDOStatement object provided.");
        }

        return $this->stmt->rowCount();
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        if (null === $this->stmt) {
            throw new DBALException("No \PDOStatement object provided.");
        }

        $this->freeze();

        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @inheritDoc
     */
    public function asRow(): ?array
    {
        if (null === $this->stmt) {
            throw new DBALException("No \PDOStatement object provided.");
        }

        $this->freeze();

        return $this->stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * @inheritDoc
     */
    public function asList(): array
    {
        if (null === $this->stmt) {
            throw new DBALException("No \PDOStatement object provided.");
        }

        $this->freeze();

        $generator = static function (PDOStatement $stmt) {
            while ($value = $stmt->fetchColumn(0)) {
                yield $value;
            }
        };

        return iterator_to_array($generator($this->stmt));
    }

    /**
     * @inheritDoc
     */
    public function asValue()
    {
        if (null === $this->stmt) {
            throw new DBALException("No \PDOStatement object provided.");
        }

        $this->freeze();

        return $this->stmt->fetchColumn(0) ?: null;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        if (null === $this->stmt) {
            throw new DBALException("No \PDOStatement object provided.");
        }

        $this->freeze();

        while ($row = $this->stmt->fetch(PDO::FETCH_ASSOC)) {
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

    public static function from(...$arguments): self
    {
        $instance = new self;
        foreach ($arguments as $argument) {
            if ($argument instanceof PDO) {
                $instance->pdo = $argument;
            }
            if ($argument instanceof PDOStatement) {
                $instance->stmt = $argument;
            }
        }

        return $instance;
    }
}
