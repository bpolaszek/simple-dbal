<?php

namespace BenTools\SimpleDBAL\Model\Adapter\Mysqli;

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
}
