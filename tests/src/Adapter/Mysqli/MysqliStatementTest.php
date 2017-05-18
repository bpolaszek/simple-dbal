<?php

namespace BenTools\SimpleDBAL\Tests\Adapter\Mysqli;

use BenTools\SimpleDBAL\Contract\ResultInterface;
use BenTools\SimpleDBAL\Model\Adapter\Mysqli\Statement;
use BenTools\SimpleDBAL\Model\Adapter\Mysqli\Result;

class MysqliStatementTest extends MysqliTestCase
{

    public static function setUpBeforeClass()
    {
        self::initConnection();
    }

    public function testPreparedWriteStmt()
    {
        $sql  = sprintf("INSERT INTO `%s` (`id`, `name`, `created_at`) VALUES (NULL, 'foo', CURRENT_TIMESTAMP )", self::$tableName);
        $stmt = self::$cnx->prepare($sql);
        $this->assertInstanceOf(Statement::class, $stmt);

        $result = self::$cnx->execute($stmt);
        $this->assertInstanceOf(ResultInterface::class, $result);
        $this->assertInstanceOf(Result::class, $result);

        /** @var \mysqli_stmt $wrappedStatement */
        $wrappedStatement = $stmt->getWrappedStatement();
        $this->assertInstanceOf(\mysqli_stmt::class, $wrappedStatement);
        $this->assertEquals(1, $wrappedStatement->affected_rows);
        $this->assertEquals(1, count($result));
    }

    public function testPreparedReadStmt()
    {
        $sql  = sprintf("INSERT INTO `%s` (`id`, `name`, `created_at`) VALUES (NULL, 'foo', CURRENT_TIMESTAMP )", self::$tableName);
        self::$cnx->getWrappedConnection()->query($sql);
        $sql = sprintf("SELECT `name` FROM `%s` WHERE `name` = 'foo'", self::$tableName);
        $stmt = self::$cnx->prepare($sql);
        $this->assertInstanceOf(Statement::class, $stmt);

        $result = self::$cnx->execute($stmt);
        $this->assertInstanceOf(ResultInterface::class, $result);
        $this->assertInstanceOf(Result::class, $result);

        $this->assertEquals('foo', $result->asValue());
    }

    public function testGetters()
    {
        $sql = sprintf("SELECT `name` FROM `%s` WHERE `name` = 'foo'", self::$tableName);
        $stmt = self::$cnx->prepare($sql);
        $this->assertInstanceOf(\mysqli_stmt::class, $stmt->getWrappedStatement());
        $this->assertEquals($sql, $stmt->getQueryString());
        $this->assertSame(self::$cnx, $stmt->getConnection());
        $this->assertNull($stmt->getValues());
    }

    public function testPreparedStmtWithNamedParameters()
    {
        $this->insertSampleData([0 => ['id' => null, 'name' => 'foo', 'created_at' => date('Y-m-d H:i:s')]]);
        $sql = sprintf("SELECT `name` FROM `%s` WHERE `name` LIKE :name", self::$tableName);
        $stmt = self::$cnx->prepare($sql);
        $stmt = $stmt->withValues(['name' => 'foo']);
        $result = self::$cnx->execute($stmt);
        $this->assertEquals('foo', $result->asValue());
    }


    /**
     * @expectedException \BenTools\SimpleDBAL\Model\Exception\ParamBindingException
     */
    public function testPreparedStmtWithNamedParametersNotProvided()
    {
        $sql  = sprintf("SELECT `name` FROM `%s` WHERE `name` = :name", self::$tableName);
        $stmt = self::$cnx->prepare($sql);
        self::$cnx->execute($stmt);
    }

    /**
     * @expectedException \BenTools\SimpleDBAL\Model\Exception\ParamBindingException
     */
    public function testPreparedStmtWithNotEnoughNamedParameters()
    {
        $sql = sprintf("SELECT `name` FROM `%s` WHERE  `id` = :id AND `name` = :name", self::$tableName);
        $stmt = self::$cnx->prepare($sql, ['id' => 1]);
        self::$cnx->execute($stmt);
    }

    /**
     * @expectedException \BenTools\SimpleDBAL\Model\Exception\ParamBindingException
     */
    public function testPreparedStmtWithTooMuchNamedParameters()
    {
        $sql = sprintf("SELECT `name` FROM `%s` WHERE  `id` = :id AND `name` = :name", self::$tableName);
        $stmt = self::$cnx->prepare($sql, ['id' => 1, 'name' => 'foo', 'bar' => 'baz']);
        self::$cnx->execute($stmt);
    }

    public function testPreparedStmtWithIncrementalParameters()
    {
        $this->insertSampleData([0 => ['id' => null, 'name' => 'foo', 'created_at' => date('Y-m-d H:i:s')]]);
        $sql = sprintf("SELECT `name` FROM `%s` WHERE `name` LIKE ?", self::$tableName);
        $stmt = self::$cnx->prepare($sql);
        $stmt = $stmt->withValues(['foo']);
        $result = self::$cnx->execute($stmt);
        $this->assertEquals('foo', $result->asValue());
    }

    /**
     * @expectedException \BenTools\SimpleDBAL\Model\Exception\ParamBindingException
     */
    public function testPreparedStmtWithIncrementalParametersNotProvided()
    {
        $sql = sprintf("SELECT `name` FROM `%s` WHERE `name` = ?", self::$tableName);
        $stmt = self::$cnx->prepare($sql);
        self::$cnx->execute($stmt);
    }

    /**
     * @expectedException \BenTools\SimpleDBAL\Model\Exception\ParamBindingException
     */
    public function testPreparedStmtWithNotEnoughIncrementalParameters()
    {
        $sql = sprintf("SELECT `name` FROM `%s` WHERE  `id` = ? AND `name` = ?", self::$tableName);
        $stmt = self::$cnx->prepare($sql, [1]);
        self::$cnx->execute($stmt);
    }

    /**
     * @expectedException \BenTools\SimpleDBAL\Model\Exception\ParamBindingException
     */
    public function testPreparedStmtWithTooMuchIncrementalParameters()
    {
        $sql = sprintf("SELECT `name` FROM `%s` WHERE  `id` = ? AND `name` = ?", self::$tableName);
        $stmt = self::$cnx->prepare($sql, [1, 'foo', 'baz']);
        self::$cnx->execute($stmt);
    }

    /**
     * @expectedException \BenTools\SimpleDBAL\Model\Exception\DBALException
     */
    public function testFallbackToDBALException()
    {
        self::$cnx->execute("S3L3CT * FR0M F00 WH3R3 B4R = 1");
    }
}
