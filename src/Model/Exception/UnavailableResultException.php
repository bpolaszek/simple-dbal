<?php

namespace BenTools\SimpleDBAL\Model\Exception;

class UnavailableResultException extends DBALException
{

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('Result object could not be instanciated.');
    }
}
