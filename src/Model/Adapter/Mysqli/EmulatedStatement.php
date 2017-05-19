<?php

namespace BenTools\SimpleDBAL\Model\Adapter\Mysqli;

use BenTools\SimpleDBAL\Contract\ResultInterface;

class EmulatedStatement extends Statement
{
    /**
     * @inheritDoc
     */
    public function __construct(MysqliAdapter $connection, array $values = null, $queryString)
    {
        $this->connection          = $connection;
        $this->values              = $values;
        $this->queryString         = $queryString;
    }

    /**
     * @inheritDoc
     */
    public function getWrappedStatement()
    {
        throw new \RuntimeException("Wrapped statement is unavailable on emulated statements.");
    }

    /**
     * @inheritDoc
     */
    public function bind(): void
    {
        $this->runnableQueryString = $this->preview();
    }

    /**
     * @return string
     */
    public function getRunnableQuery()
    {
        if (null === $this->runnableQueryString) {
            throw new \RuntimeException("Unbound query, run bind() before.");
        }
        return $this->runnableQueryString;
    }

    /**
     * @inheritDoc
     */
    public function createResult(): ResultInterface
    {
        $this->bind();
        $mysqliResult = $this->getConnection()->getWrappedConnection()->query($this->getRunnableQuery());
        return new Result($this->getConnection()->getWrappedConnection(), $mysqliResult instanceof \mysqli_result ? $mysqliResult : null);
    }
}
