<?php

namespace BenTools\SimpleDBAL\Tests\Adapter\PDO;

class WriteResultTest extends ReadResultTest
{

    public function testCount()
    {
        $sql = sprintf("INSERT INTO `%s` VALUES (?, ?, ?), (?, ?, ?)", self::$tableName);
        $result = self::$cnx->execute($sql, [
            $this->sampleData[0]['id'],
            $this->sampleData[0]['name'],
            $this->sampleData[0]['created_at'],
            $this->sampleData[1]['id'],
            $this->sampleData[1]['name'],
            $this->sampleData[1]['created_at'],
        ]);

        $this->assertCount(2, $result);
    }

    public function testLastInsertId()
    {
        $sql = sprintf("INSERT INTO `%s` VALUES (?, ?, ?)", self::$tableName);
        $result = self::$cnx->execute($sql, [
            null,
            $this->sampleData[0]['name'],
            $this->sampleData[0]['created_at'],
        ]);
        $this->assertEquals(1, $result->getLastInsertId());
        $result = self::$cnx->execute($sql, [
            null,
            $this->sampleData[1]['name'],
            $this->sampleData[1]['created_at'],
        ]);
        $this->assertEquals(2, $result->getLastInsertId());
    }
}
