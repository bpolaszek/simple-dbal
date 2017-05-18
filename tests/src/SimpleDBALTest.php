<?php

namespace BenTools\SimpleDBAL\Tests;

use BenTools\SimpleDBAL\Model\Adapter\Mysqli\MysqliAdapter;
use BenTools\SimpleDBAL\Model\Adapter\PDO\PDOAdapter;
use BenTools\SimpleDBAL\Model\SimpleDBAL;
use PHPUnit\Framework\TestCase;

class SimpleDBALTest extends TestCase
{

    public function testAutomaticFactory()
    {
        $credentials = TestSuite::getCredentialsFromSettings(TestSuite::getSettings());
        $dbal = SimpleDBAL::factory($credentials);
        $this->assertInstanceOf(PDOAdapter::class, $dbal);
    }

    public function testPDOFactory()
    {
        $credentials = TestSuite::getCredentialsFromSettings(TestSuite::getSettings());
        $dbal = SimpleDBAL::factory($credentials, SimpleDBAL::PDO);
        $this->assertInstanceOf(PDOAdapter::class, $dbal);
    }

    public function testMysqliFactory()
    {
        $credentials = TestSuite::getCredentialsFromSettings(TestSuite::getSettings());
        $dbal = SimpleDBAL::factory($credentials, SimpleDBAL::MYSQLI);
        $this->assertInstanceOf(MysqliAdapter::class, $dbal);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidFactory()
    {
        $credentials = TestSuite::getCredentialsFromSettings(TestSuite::getSettings());
        $dbal = SimpleDBAL::factory($credentials, 'dummy');
    }
}
