<?php

namespace BenTools\SimpleDBAL\Model\Adapter\Mysqli;

use BenTools\SimpleDBAL\Contract\AdapterInterface;
use BenTools\SimpleDBAL\Contract\ResultInterface;
use BenTools\SimpleDBAL\Contract\StatementInterface;
use BenTools\SimpleDBAL\Model\Exception\ParamBindingException;
use BenTools\SimpleDBAL\Model\StatementTrait;
use mysqli_result;

class Statement implements StatementInterface
{

    use StatementTrait;

    /**
     * @var MysqliAdapter
     */
    private $connection;

    /**
     * @var \mysqli_stmt
     */
    private $stmt;

    /**
     * @var string
     */
    private $queryString;

    /**
     * @var string
     */
    private $runnableQueryString;

    /**
     * MysqliStatement constructor.
     * @param MysqliAdapter $connection
     * @param \mysqli_stmt $stmt
     * @param array $values
     * @param string $queryString
     * @param string $runnableQueryString
     */
    public function __construct(MysqliAdapter $connection, \mysqli_stmt $stmt, array $values = null, string $queryString, string $runnableQueryString = null)
    {
        $this->connection          = $connection;
        $this->stmt                = $stmt;
        $this->values              = $values;
        $this->queryString         = $queryString;
        $this->runnableQueryString = $runnableQueryString;
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
        return $this->queryString;
    }

    /**
     * @inheritDoc
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
            if ($this->hasNamedPlaceholders() && $this->connection->shouldResolveNamedParameters()) {
                $values = $this->resolveValues();
            } else {
                $values = $this->values;
            }

            $values = array_map([$this, 'toScalar'], $values);
            $types = $this->getTypesFor($values);
            $this->stmt->bind_param($types, ...$values);
        }
    }

    /**
     * @param array $values
     * @return string
     */
    private function getTypesFor(array $values): string
    {
        $types = '';
        foreach ($values as $key => $value) {
            $types .= $this->getMysqliType($value);
        }
        return $types;
    }

    /**
     * @return array
     */
    private function resolveValues(): array
    {
        preg_match_all('#:([a-zA-Z0-9_]+)#', $this->queryString, $matches);
        $placeholders = $matches[1];
        $values       = [];
        if (count(array_unique($placeholders)) !== count(array_keys($this->values))) {
            throw new ParamBindingException("Placeholders count does not match values count.", 0, null, $this);
        }
        foreach ($placeholders as $placeholder) {
            if (!array_key_exists($placeholder, $this->values)) {
                throw new ParamBindingException(sprintf("Unable to find placeholder %s into the list of values.", $placeholder), 0, null, $this);
            }
            $values[] = $this->values[$placeholder];
        }
        return $values;
    }

    /**
     * Attempt to convert non-scalar values.
     *
     * @param $value
     * @return string
     */
    protected function toScalar($value)
    {
        if (is_scalar($value)) {
            return $value;
        } elseif (null === $value) {
            return 'NULL';
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
     * @inheritDoc
     */
    public function preview(): string
    {
        if (!$this->hasValues()) {
            return $this->queryString;
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
                    return (string) "'" . addslashes($this->getConnection()->getWrappedConnection()->real_escape_string($value)) . "'";
            }
        };

        $keywords = [];
        $preview  = $this->queryString;

        # Case of question mark placeholders
        if ($this->hasAnonymousPlaceholders()) {
            if (count($this->values) !== preg_match_all("/([\?])/", $this->queryString)) {
                throw new ParamBindingException("Number of variables doesn't match number of parameters in prepared statement", 0, null, $this);
            }

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

            $nbPlaceholders = preg_match_all('#:([a-zA-Z0-9_]+)#', $this->queryString, $placeholders);

            if ($nbPlaceholders > 0 && count(array_unique($placeholders[1])) !== count($this->values)) {
                throw new ParamBindingException("Number of variables doesn't match number of parameters in prepared statement", 0, null, $this);
            }

            foreach ($keywords as $keyword) {
                $pattern = "/(\:\b" . $keyword . "\b)/i";
                $preview = preg_replace($pattern, $escape($this->values[$keyword]), $preview);
            }
        }
        return $preview;
    }

    /**
     * @param $value
     * @return string
     */
    protected function getMysqliType($value)
    {
        if (!is_scalar($value)) {
            throw new \InvalidArgumentException(sprintf("Can only cast scalar variables, %s given.", gettype($value)));
        }
        $cast = gettype($value);
        switch ($cast) {
            case 'double':
                return 'd';
            case 'integer':
                return 'i';
            default:
                return 's';
        }
    }
    /**
     * @return Result
     */
    public function createResult(): ResultInterface
    {
        $result = $this->getWrappedStatement()->get_result();
        $mysqli = $this->getConnection()->getWrappedConnection();
        return !$result instanceof mysqli_result ? new Result($mysqli) : new Result($mysqli, $result);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->queryString;
    }
}
