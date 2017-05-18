<?php

namespace BenTools\SimpleDBAL\Contract;

interface AdapterInterface extends ConnectionInterface
{
    /**
     * Return the wrapped connection object.
     *
     * @return object
     */
    public function getWrappedConnection();

    /**
     * Return the credentials used to connect.
     *
     * @return CredentialsInterface
     */
    public function getCredentials(): CredentialsInterface;
}
