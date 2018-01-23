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

    private $storage = [];

    /**
     * @var bool
     */
    private $storageEnabled = true;

    /**
     * Result constructor.
     * @param mysqli        $mysqli
     * @param mysqli_stmt   $stmt
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
    public function count()
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
        if (empty($this->storage['array'])) {
            if ($this->shouldResetResultset()) {
                $this->resetResultset();
            }

            $result = $this->result->fetch_all(MYSQLI_ASSOC);

            if (true === $this->storageEnabled) {
                $this->storage['array'] = $result;
            }

            return $result;
        }
        return $this->storage['array'];
    }

    /**
     * @inheritDoc
     */
    public function asRow(): ?array
    {
        if (null === $this->result) {
            throw new DBALException("No mysqli_result object provided.");
        }
        if (empty($this->storage['row'])) {
            if (isset($this->storage['array'][0])) {
                $this->storage['row'] = &$this->storage['array'][0];
            } else {
                if ($this->shouldResetResultset()) {
                    $this->resetResultset();
                }

                $result = $this->result->fetch_array(MYSQLI_ASSOC) ?: null;

                if (true === $this->storageEnabled) {
                    $this->storage['row'] = $result;
                }

                return $result;
            }
        }
        return $this->storage['row'];
    }

    /**
     * @inheritDoc
     */
    public function asList(): array
    {
        if (null === $this->result) {
            throw new DBALException("No mysqli_result object provided.");
        }
        if (empty($this->storage['list'])) {
            if (!empty($this->storage['array'])) {
                $this->storage['list'] = array_column($this->storage['array'], array_keys($this->storage['array'][0])[0]);
            } else {
                if ($this->shouldResetResultset()) {
                    $this->resetResultset();
                }

                $generator = function (\mysqli_result $result) {
                    while ($row = $result->fetch_array(MYSQLI_NUM)) {
                        yield $row[0];
                    }
                };
                $result = iterator_to_array($generator($this->result));

                if (true === $this->storageEnabled) {
                    $this->storage['list'] = $result;
                }

                return $result;
            }
        }
        return $this->storage['list'];
    }

    /**
     * @inheritDoc
     */
    public function asValue()
    {
        if (null === $this->result) {
            throw new DBALException("No mysqli_result object provided.");
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
                    $this->resetResultset();
                }

                $row = $this->result->fetch_array(MYSQLI_NUM);
                $result = $row ? $row[0] : null;

                if (true === $this->storageEnabled) {
                    $this->storage['value'] = $result;
                }

                return $result;
            }
        }
        return $this->storage['value'];
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        if (null === $this->result) {
            throw new DBALException("No mysqli_result object provided.");
        }
        if (!empty($this->storage['array'])) {
            foreach ($this->storage['array'] as $key => $value) {
                yield $key => $value;
            }
        } else {
            if ($this->shouldResetResultset()) {
                $this->resetResultset();
            }

            while ($row = $this->result->fetch_array(MYSQLI_ASSOC)) {
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


    /**
     * Reset the resultset.
     */
    private function resetResultset()
    {
        if (null !== $this->stmt) {
            $this->stmt->execute();
            $this->result = $this->stmt->get_result();
        }
    }

    /**
     * @return ResultInterface
     */
    public function withoutStorage(): ResultInterface
    {
        $clone = clone $this;
        $clone->storage = [];
        $clone->storageEnabled = false;
        return $clone;
    }
}
