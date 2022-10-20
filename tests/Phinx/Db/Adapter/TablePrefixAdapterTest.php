<?php

namespace Test\Phinx\Db\Adapter;

use Phinx\Db\Adapter\TablePrefixAdapter;
use Phinx\Db\Table;
use Phinx\Db\Table\Column;
use Phinx\Db\Table\ForeignKey;
use PHPUnit\Framework\TestCase;

class TablePrefixAdapterTest extends TestCase
{
    /**
     * @var \Phinx\Db\Adapter\TablePrefixAdapter
     */
    private $adapter;

    /**
     * @var \Phinx\Db\Adapter\AdapterInterface
     */
    private $mock;

    public function setUp(): void
    {
        $options = [
            'table_prefix' => 'pre_',
            'table_suffix' => '_suf',
        ];

        $this->mock = $this->getMockBuilder('\Phinx\Db\Adapter\PdoAdapter')
            ->setConstructorArgs([[]])
            ->getMock();

        $this->mock
            ->expects($this->any())
            ->method('getOption')
            ->with($this->logicalOr(
                $this->equalTo('table_prefix'),
                $this->equalTo('table_suffix')
            ))
            ->will($this->returnCallback(function ($option) use ($options) {
                return $options[$option];
            }));

        $this->adapter = new TablePrefixAdapter($this->mock);
    }

    public function tearDown(): void
    {
        unset($this->adapter);
        unset($this->mock);
    }

    public function testGetAdapterTableName()
    {
        $tableName = $this->adapter->getAdapterTableName('table');
        $this->assertEquals('pre_table_suf', $tableName);
    }

    public function testHasTable()
    {
        $this->mock
            ->expects($this->once())
            ->method('hasTable')
            ->with($this->equalTo('pre_table_suf'));

        $this->adapter->hasTable('table');
    }

    public function testHasColumn()
    {
        $this->mock
            ->expects($this->once())
            ->method('hasColumn')
            ->with(
                $this->equalTo('pre_table_suf'),
                $this->equalTo('column')
            );

        $this->adapter->hasColumn('table', 'column');
    }

    public function testInsertData()
    {
        $row = ['column1' => 'value3'];

        $this->mock
            ->expects($this->once())
            ->method('bulkinsert')
            ->with($this->callback(
                function ($table) {
                    return $table->getName() == 'pre_table_suf';
                },
                $this->equalTo($row)
            ));

        $table = new Table('table', [], $this->adapter);
        $table->insert($row)
              ->save();
    }
}
