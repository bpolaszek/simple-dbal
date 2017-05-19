<?php

namespace BenTools\SimpleDBAL\Model\Adapter\PDO;

use BenTools\SimpleDBAL\Contract\ResultInterface;
use BenTools\SimpleDBAL\Model\Exception\DBALException;
use PDO;
use PDOStatement;

class Result implements \IteratorAggregate, ResultInterface, \Countable
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var Statement
     */
    private $stmt;

    /**
     * @var array
     */
    private $storage = [];

    /**
     * Result constructor.
     * @param PDO $pdo
     * @param Statement $stmt
     */
    public function __construct(PDO $pdo, PDOStatement $stmt = null)
    {
        $this->pdo = $pdo;
        $this->stmt = $stmt;
    }

    /**
     * @inheritDoc
     */
    public function getLastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
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
        if (!empty($this->storage['row']) || !empty($this->storage['value']) || !empty($this->storage['list']) || !empty($this->storage['yield'])) {
            $this->stmt->execute();
        }
        if (empty($this->storage['array'])) {


            if ($this->shouldResetResultset()) {
                $this->stmt->execute();
            }

            $this->storage['array'] = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->storage['array'];
    }

    /**
     * @inheritDoc
     */
    public function asRow(): ?array
    {
        if (null === $this->stmt) {
            throw new DBALException("No \PDOStatement object provided.");
        }
        if (empty($this->storage['row'])) {
            if (isset($this->storage['array'][0])) {
                $this->storage['row'] = &$this->storage['array'][0];
            } else {

                if ($this->shouldResetResultset()) {
                    $this->stmt->execute();
                }

                $this->storage['row'] = $this->stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        }
        return $this->storage['row'];
    }

    /**
     * @inheritDoc
     */
    public function asList(): array
    {
        if (null === $this->stmt) {
            throw new DBALException("No \PDOStatement object provided.");
        }
        if (empty($this->storage['list'])) {
            if (!empty($this->storage['array'])) {
                $this->storage['list'] = array_column($this->storage['array'], array_keys($this->storage['array'][0])[0]);
            } else {

                if ($this->shouldResetResultset()) {
                    $this->stmt->execute();
                }

                $generator = function (\PDOStatement $stmt) {
                    while ($value = $stmt->fetchColumn(0)) {
                        yield $value;
                    }
                };
                $this->storage['list'] = iterator_to_array($generator($this->stmt));
            }
        }
        return $this->storage['list'];
    }

    /**
     * @inheritDoc
     */
    public function asValue()
    {
        if (null === $this->stmt) {
            throw new DBALException("No \PDOStatement object provided.");
        }
        if (empty($this->storage['value'])) {
            if (!empty($this->storage['list'][0])) {
                $this->storage['value'] = $this->storage['list'][0];
            } elseif (!empty($this->storage['row'])) {
                $this->storage['value'] = array_values($this->storage['row'])[0];
            } elseif (!empty($this->storage['array'])) {
                $this->storage['value'] = array_values($this->storage['array'][0])[0];
            } else {

                if ($this->shouldResetResultset()) {
                    $this->stmt->execute();
                }

                $this->storage['value'] = $this->stmt->fetchColumn(0) ?: null;
            }
        }
        return $this->storage['value'];
    }


    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        if (null === $this->stmt) {
            throw new DBALException("No \PDOStatement object provided.");
        }

        // If asArray() was called earlier, iterate over the stored resultset.
        if (!empty($this->storage['array'])) {
            foreach ($this->storage['array'] as $key => $value) {
                yield $key => $value;
            }
        } else {

            $wrappedStmt = $this->stmt;

            if ($this->shouldResetResultset()) {
                $this->stmt->execute();
            }

            while ($row = $wrappedStmt->fetch(PDO::FETCH_ASSOC)) {
                if (empty($this->storage['yield'])) {
                    $this->storage['yield'] = true;
                }
                yield $row;
            }
        }
    }

    /**
     * If asRow(), asList() or asValue() was called earlier, the iterator may be incomplete.
     * In such case we need to rewind the iterator by executing the statement a second time.
     * You should avoid to call getIterator() and asRow(), etc. with the same resultset.
     *
     * @return bool
     */
    private function shouldResetResultset(): bool
    {
        return !empty($this->storage['row']) || !empty($this->storage['value']) || !empty($this->storage['list']) || !empty($this->storage['yield']);
    }
}