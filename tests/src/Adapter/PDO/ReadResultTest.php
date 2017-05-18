<?php

namespace BenTools\SimpleDBAL\Tests\Adapter\PDO;

use BenTools\SimpleDBAL\Model\Adapter\PDO\Result;
use PHPUnit\Framework\TestCase;

class ReadResultTest extends PDOTestCase
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
        $this->assertEquals($this->expectedResult, iterator_to_array($result));
    }

    public function testArrayAfterIterator()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        iterator_to_array($result);
        $this->assertEquals($this->expectedResult, $result->asArray());
        $this->assertEquals($this->expectedResult, $result->asArray());
    }

    public function testArrayAfterIncompleteIterator()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        foreach ($result as $item) {
            break;
        }
        $this->assertEquals($this->expectedResult, $result->asArray());
        $this->assertEquals($this->expectedResult, $result->asArray());
    }

    public function testArray()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->assertEquals($this->expectedResult, $result->asArray());
        $this->assertEquals($this->expectedResult, $result->asArray());
    }

    public function testIteratorAfterArray()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $result->asArray();
        $this->assertEquals($this->expectedResult, iterator_to_array($result));
        $this->assertEquals($this->expectedResult, iterator_to_array($result));
    }

    public function testRow()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->assertEquals($this->expectedResult[0], $result->asRow());
        $this->assertEquals($this->expectedResult[0], $result->asRow());
    }

    public function testRowAfterArray()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $result->asArray();
        $this->assertEquals($this->expectedResult[0], $result->asRow());
        $this->assertEquals($this->expectedResult[0], $result->asRow());
    }

    public function testList()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->assertEquals(array_column($this->expectedResult, 'id'), $result->asList());
        $this->assertEquals(array_column($this->expectedResult, 'id'), $result->asList());
    }

    public function testListAfterArray()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $result->asArray();
        $this->assertEquals(array_column($this->expectedResult, 'id'), $result->asList());
        $this->assertEquals(array_column($this->expectedResult, 'id'), $result->asList());
    }

    public function testValue()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $this->assertEquals($this->expectedResult[0]['id'], $result->asValue());
        $this->assertEquals($this->expectedResult[0]['id'], $result->asValue());
    }

    public function testValueAfterList()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $result->asList();
        $this->assertEquals($this->expectedResult[0]['id'], $result->asValue());
        $this->assertEquals($this->expectedResult[0]['id'], $result->asValue());
    }

    public function testValueAfterRow()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $result->asRow();
        $this->assertEquals($this->expectedResult[0]['id'], $result->asValue());
        $this->assertEquals($this->expectedResult[0]['id'], $result->asValue());
    }

    public function testValueAfterArray()
    {
        $this->insertSampleData($this->sampleData);
        $result = $this->fetchResult();
        $result->asArray();
        $this->assertEquals($this->expectedResult[0]['id'], $result->asValue());
        $this->assertEquals($this->expectedResult[0]['id'], $result->asValue());
    }
}
