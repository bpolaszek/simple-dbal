<?php

namespace BenTools\SimpleDBAL\Tests;

use BenTools\SimpleDBAL\Contract\CredentialsInterface;
use BenTools\SimpleDBAL\Model\Credentials;
use Symfony\Component\Yaml\Yaml;

class TestSuite
{
    /**
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'database_host' => $_SERVER['DATABASE_HOST'],
            'database_user' => $_SERVER['DATABASE_USER'],
            'database_password' => $_SERVER['DATABASE_PASSWORD'],
            'database_name' => $_SERVER['DATABASE_NAME'],
            'test_table_name' => 'simpledbal_test',
        ];
    }

    /**
     * @return CredentialsInterface
     */
    public static function getCredentialsFromSettings(array $settings): CredentialsInterface
    {
        return new Credentials(
            $settings['database_host'],
            $settings['database_user'],
            $settings['database_password'],
            $settings['database_name']
        );
    }
}
