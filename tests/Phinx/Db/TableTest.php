<?php

namespace Test\Phinx\Db;

use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{

    public function testInsert()
    {
        $adapterStub = $this->getMockBuilder('\Phinx\Db\Adapter\MysqlAdapter')
            ->setConstructorArgs([[]])
            ->getMock();
        $table = new \Phinx\Db\Table('ntable', [], $adapterStub);
        $data = [
            'column1' => 'value1',
            'column2' => 'value2',
        ];
        $table->insert($data);
        $expectedData = [
            $data,
        ];
        $this->assertEquals($expectedData, $table->getData());
    }

    public function testInsertSaveData()
    {
        $adapterStub = $this->getMockBuilder('\Phinx\Db\Adapter\MysqlAdapter')
            ->setConstructorArgs([[]])
            ->getMock();
        $table = new \Phinx\Db\Table('ntable', [], $adapterStub);
        $data = [
            [
                'column1' => 'value1',
            ],
            [
                'column1' => 'value2',
            ],
        ];

        $moreData = [
            [
                'column1' => 'value3',
            ],
            [
                'column1' => 'value4',
            ],
        ];

        $adapterStub->expects($this->exactly(1))
                    ->method('bulkinsert')
                    ->with($table, [$data[0], $data[1], $moreData[0], $moreData[1]]);

        $table->insert($data)
              ->insert($moreData)
              ->save();
    }

    public function testResetAfterAddingData()
    {
        $adapterStub = $this->getMockBuilder('\Phinx\Db\Adapter\MysqlAdapter')
            ->setConstructorArgs([[]])
            ->getMock();
        $table = new \Phinx\Db\Table('ntable', [], $adapterStub);
        $columns = ["column1"];
        $data = [["value1"]];
        $table->insert($columns, $data)->save();
        $this->assertEquals([], $table->getData());
    }
}
