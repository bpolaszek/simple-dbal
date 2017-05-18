<?php

namespace BenTools\SimpleDBAL\Model\Adapter\Mysqli;

use BenTools\SimpleDBAL\Contract\ResultInterface;
use BenTools\SimpleDBAL\Model\Exception\DBALException;
use mysqli;
use mysqli_result;

class Result implements ResultInterface
{
    /**
     * @var mysqli
     */
    private $mysqli;

    /**
     * @var mysqli_result
     */
    private $result;

    private $storage = [];

    /**
     * Result constructor.
     * @param mysqli $mysqli
     * @param mysqli_result $result
     */
    public function __construct(mysqli $mysqli, mysqli_result $result = null)
    {
        $this->mysqli = $mysqli;
        $this->result = $result;
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
            $this->storage['array'] = $this->result->fetch_all(MYSQLI_ASSOC);
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
                $this->storage['row'] = $this->result->fetch_array(MYSQLI_ASSOC) ?: null;
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
                $generator = function (\mysqli_result $result) {
                    while ($row = $result->fetch_array(MYSQLI_NUM)) {
                        yield $row[0];
                    }
                };
                $this->storage['list'] = iterator_to_array($generator($this->result));
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
                $row                    = $this->result->fetch_array(MYSQLI_NUM);
                $this->storage['value'] = $row ? $row[0] : null;
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
            $storage = [];
            while ($row = $this->result->fetch_array(MYSQLI_ASSOC)) {
                $storage[] = $row;
                yield $row;
            }
            $this->storage['array'] = $storage;
        }
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
}
