<?php

namespace BenTools\SimpleDBAL\Model;

use BenTools\SimpleDBAL\Contract\AdapterInterface;
use BenTools\SimpleDBAL\Contract\CredentialsInterface;
use BenTools\SimpleDBAL\Model\Adapter\Mysqli\MysqliAdapter;
use BenTools\SimpleDBAL\Model\Adapter\PDO\PDOAdapter;

class SimpleDBAL
{

    const PDO    = 'pdo';
    const MYSQLI = 'mysqli';

    /**
     * @param CredentialsInterface $credentials
     * @param string $adapter
     * @return AdapterInterface
     */
    public static function factory(CredentialsInterface $credentials, string $adapter = self::PDO, array $options = null): AdapterInterface
    {
        switch ($adapter) {
            case self::PDO:
                return PDOAdapter::factory($credentials, $options);

            case self::MYSQLI:
                return MysqliAdapter::factory($credentials, $options);

            default:
                throw new \InvalidArgumentException("Invalid adapter.");
        }
    }
}
