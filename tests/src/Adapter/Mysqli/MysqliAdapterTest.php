<?php

namespace BenTools\SimpleDBAL\Tests\Adapter\Mysqli;

use BenTools\SimpleDBAL\Model\Adapter\Mysqli\MysqliAdapter;
use BenTools\SimpleDBAL\Tests\TestSuite;

class MysqliAdapterTest extends MysqliTestCase
{

    /**
     * @return MysqliAdapter
     */
    public function testInit(): MysqliAdapter
    {
        $settings        = TestSuite::getSettings();
        $credentials     = TestSuite::getCredentialsFromSettings($settings);
        self::$tableName = $settings['test_table_name'];
        $connection = MysqliAdapter::factory($credentials);

        $this->assertInstanceOf(MysqliAdapter::class, $connection);
        $this->assertInstanceOf(\mysqli::class, $connection->getWrappedConnection());
        $this->assertSame($credentials, $connection->getCredentials());
        self::$cnx = $connection;
        return $connection;
    }

    /**
     * @expectedException \BenTools\SimpleDBAL\Model\Exception\AccessDeniedException
     */
    public function testInitFails()
    {
        $settings                      = TestSuite::getSettings();
        $settings['database_password'] = uniqid();
        $credentials                   = TestSuite::getCredentialsFromSettings($settings);
        self::$tableName               = $settings['test_table_name'];
        $connection                    = MysqliAdapter::factory($credentials);
    }
}
