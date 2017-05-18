<?php

namespace BenTools\SimpleDBAL\Model\Exception;

use BenTools\SimpleDBAL\Contract\StatementInterface;
use Throwable;

abstract class StatementException extends DBALException
{
    /**
     * @var StatementInterface
     */
    private $statement;

    /**
     * @inheritDoc
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null, StatementInterface $statement)
    {
        parent::__construct($message, $code, $previous);
        $this->statement = $statement;
    }

    /**
     * @return StatementInterface
     */
    public function getStatement(): StatementInterface
    {
        return $this->statement;
    }
}
