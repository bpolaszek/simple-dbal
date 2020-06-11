<?php

namespace BenTools\SimpleDBAL\Tests\Adapter\Mysqli;

use BenTools\SimpleDBAL\Model\Adapter\Mysqli\Result;
use BenTools\SimpleDBAL\Model\Exception\DBALException;

class ReadResultTest extends MysqliTestCase
{
    protected $sampleData = [
        [
            'id' => null,
            'name' => 'foo',
            'created_at' => '2017-01-01 00:00:00'
        ],
        [
            'id' => null,
            'name' => 'bar',
            'created_at' => '2017-02-28 23:59:59'
        ],
    ];

    protected $expectedResult = [
        [
            'id' => 1,
            'name' => 'foo',
            'created_at' => '2017-01-01 00:00:00'
        ],
        [
            'id' => 2,
            'name' => 'bar',
            'created_at' => '2017-02-28 23:59:59'
        ],
    ];

    public static function setUpBeforeClass()
    {
        self::initConnection();
    }

    protected function fetchResult(): Result
    {
        return self::$cnx->execute(sprintf("SELECT * FROM `%s` ORDER BY id", self::$tableName));
    }

    public function testIterator()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->assertEquals($this->expectedResult, iterator_to_array($result));
    }

    public function testIteratorYellsWhenCalledTwice()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('This result is frozen. You have to re-execute this statement.');
        iterator_to_array($result);
        iterator_to_array($result);
    }

    public function testArray()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->assertEquals($this->expectedResult, $result->asArray());
    }

    public function testArrayYellsWhenCalledTwice()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('This result is frozen. You have to re-execute this statement.');
        $result->asArray();
        $result->asArray();
    }

    public function testRow()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->assertEquals($this->expectedResult[0], $result->asRow());
    }

    public function testRowYellsWhenCalledTwice()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('This result is frozen. You have to re-execute this statement.');
        $result->asRow();
        $result->asRow();
    }

    public function testList()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->assertEquals(array_column($this->expectedResult, 'id'), $result->asList());
    }

    public function testListYellsWhenCalledTwice()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('This result is frozen. You have to re-execute this statement.');
        $result->asList();
        $result->asList();
    }

    public function testValue()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->assertEquals($this->expectedResult[0]['id'], $result->asValue());
    }

    public function testValueYellsWhenCalledTwice()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('This result is frozen. You have to re-execute this statement.');
        $result->asValue();
        $result->asValue();
    }
}
