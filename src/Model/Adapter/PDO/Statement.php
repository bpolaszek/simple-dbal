<?php

namespace BenTools\SimpleDBAL\Model\Adapter\PDO;

use BenTools\SimpleDBAL\Contract\AdapterInterface;
use BenTools\SimpleDBAL\Contract\StatementInterface;
use BenTools\SimpleDBAL\Contract\ResultInterface;
use BenTools\SimpleDBAL\Model\StatementTrait;
use PDO;
use PDOStatement;

class Statement implements StatementInterface
{
    use StatementTrait;

    /**
     * @var PDOAdapter
     */
    private $connection;

    /**
     * @var PDOStatement
     */
    private $stmt;

    /**
     * PDOStatement constructor.
     * @param PDOAdapter $connection
     * @param PDOStatement $statement
     * @param array $values
     */
    public function __construct(PDOAdapter $connection, PDOStatement $statement, array $values = null)
    {
        $this->connection = $connection;
        $this->stmt       = $statement;
        $this->values     = $values;
    }

    /**
     * @inheritDoc
     */
    final public function getConnection(): AdapterInterface
    {
        return $this->connection;
    }

    /**
     * @inheritDoc
     */
    public function getQueryString(): string
    {
        return $this->stmt->queryString;
    }

    /**
     * @inheritDoc
     * @return PDOStatement
     */
    public function getWrappedStatement()
    {
        return $this->stmt;
    }

    /**
     * @inheritDoc
     */
    public function bind(): void
    {
        if ($this->hasValues()) {
            if (0 === array_keys($this->getValues())[0]) {
                $this->bindNumericParameters();
            } else {
                $this->bindNamedParameters();
            }
        }
    }

    /**
     * Bind :named parameters
     */
    protected function bindNamedParameters(): void
    {
        foreach ($this->getValues() as $key => $value) {
            $value = $this->toScalar($value);
            $this->getWrappedStatement()->bindValue(sprintf(':%s', $key), $value, $this->getPdoType($value));
        }
    }

    /**
     * Bind ? parameters
     */
    protected function bindNumericParameters(): void
    {
        $values = $this->getValues();
        $cnt    = count($values);
        for ($index0 = 0, $index1 = 1; $index0 < $cnt; $index0++, $index1++) {
            $value = $this->toScalar($values[$index0]);
            $this->getWrappedStatement()->bindValue($index1, $value, $this->getPdoType($value));
        }
    }

    /**
     * Attempt to convert non-scalar values.
     *
     * @param $value
     * @return string
     */
    protected function toScalar($value)
    {
        if (is_scalar($value) || null === $value) {
            return $value;
        } else {
            if (is_object($value)) {
                if (is_callable([$value, '__toString'])) {
                    return (string) $value;
                } elseif ($value instanceof \DateTimeInterface) {
                    return $value->format('Y-m-d H:i:s');
                } else {
                    throw new \InvalidArgumentException(sprintf("Cast of class %s is impossible", get_class($value)));
                }
            } else {
                throw new \InvalidArgumentException(sprintf("Cast of type %s is impossible", gettype($value)));
            }
        }
    }

    /**
     * @param $var
     * @return int
     */
    protected function getPdoType($value)
    {
        if (!is_scalar($value) && null !== $value) {
            throw new \InvalidArgumentException("Can only cast scalar variables.");
        }
        switch (strtolower(gettype($value))) :
            case 'integer':
                return PDO::PARAM_INT;
            case 'boolean':
                return PDO::PARAM_BOOL;
            case 'NULL':
                return PDO::PARAM_NULL;
            case 'double':
            case 'string':
            default:
                return PDO::PARAM_STR;
        endswitch;
    }

    /**
     * @inheritDoc
     */
    public function preview(): string
    {
        if (!$this->hasValues()) {
            return $this->stmt->queryString;
        }

        $escape = function ($value) {
            if (null === $value) {
                return 'NULL';
            }
            $value = $this->toScalar($value);
            $type = gettype($value);
            switch ($type) {
                case 'boolean':
                    return (int) $value;
                case 'double':
                case 'integer':
                    return $value;
                default:
                    return (string) "'" . addslashes($value) . "'";
            }
        };

        $keywords = [];
        $preview  = $this->stmt->queryString;

        # Case of question mark placeholders
        if ($this->hasAnonymousPlaceholders()) {
            foreach ($this->values as $value) {
                $preview = preg_replace("/([\?])/", $escape($value), $preview, 1);
            }
        } # Case of named placeholders
        else {
            foreach ($this->values as $key => $value) {
                if (!in_array($key, $keywords, true)) {
                    $keywords[] = $key;
                }
            }
            foreach ($keywords as $keyword) {
                $pattern = "/(\:\b" . $keyword . "\b)/i";
                $preview = preg_replace($pattern, $escape($this->values[$keyword]), $preview);
            }
        }
        return $preview;
    }

    /**
     * @inheritDoc
     */
    public function createResult(): ResultInterface
    {
        return new Result($this->getConnection()->getWrappedConnection(), $this->getWrappedStatement());
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->getQueryString();
    }
}
