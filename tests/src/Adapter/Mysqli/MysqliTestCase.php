<?php

namespace BenTools\SimpleDBAL\Tests\Adapter\Mysqli;

use BenTools\SimpleDBAL\Model\Adapter\Mysqli\MysqliAdapter;
use BenTools\SimpleDBAL\Tests\TestSuite;
use PHPUnit\Framework\TestCase;

abstract class MysqliTestCase extends TestCase
{

    /**
     * @var MysqliAdapter
     */
    protected static $cnx;
    protected static $tableName;

    protected static function initConnection()
    {
        $settings        = TestSuite::getSettings();
        $credentials     = TestSuite::getCredentialsFromSettings($settings);
        self::$cnx       = MysqliAdapter::factory($credentials);
        self::$tableName = $settings['test_table_name'];
    }

    protected function insertSampleData(array $data)
    {
        if (isset($data[0])) {
            foreach ($data as $item) {
                $stmt = self::$cnx->getWrappedConnection()->prepare(sprintf("INSERT INTO %s VALUES (?, ?, ?)", self::$tableName));
                $stmt->bind_param('iss', $item['id'], $item['name'], $item['created_at']);
                $stmt->execute();
            }
        }
    }

    public function setUp()
    {
        if (null !== self::$cnx) {
            $sql = sprintf("CREATE TEMPORARY TABLE `%s` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    `created_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`)
                    ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;", self::$tableName);

            self::$cnx->getWrappedConnection()->query($sql);
        }
    }

    public function tearDown()
    {
        if (null !== self::$cnx) {
            $sql = sprintf("DROP TABLE IF EXISTS %s", self::$tableName);
            self::$cnx->getWrappedConnection()->query($sql);
        }
    }
}
