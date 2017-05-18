<?php

namespace BenTools\SimpleDBAL\Tests\Adapter\PDO;

use BenTools\SimpleDBAL\Model\Adapter\PDO\PDOAdapter;
use BenTools\SimpleDBAL\Tests\TestSuite;
use PDO;

class PDOAdapterTest extends PDOTestCase
{

    /**
     * @return PDOAdapter
     */
    public function testInit(): PDOAdapter
    {
        $settings        = TestSuite::getSettings();
        $credentials     = TestSuite::getCredentialsFromSettings($settings);
        self::$tableName = $settings['test_table_name'];
        $connection = PDOAdapter::factory($credentials);

        $this->assertInstanceOf(PDOAdapter::class, $connection);
        $this->assertInstanceOf(PDO::class, $connection->getWrappedConnection());
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $connection->getWrappedConnection()->getAttribute(PDO::ATTR_ERRMODE));
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
        $connection                    = PDOAdapter::factory($credentials);
    }
}
