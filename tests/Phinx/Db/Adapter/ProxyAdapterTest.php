<?php

namespace Test\Phinx\Db\Adapter;

use Phinx\Db\Adapter\PdoAdapter;
use Phinx\Db\Adapter\ProxyAdapter;
use Phinx\Db\Table;
use Phinx\Db\Table\ForeignKey;
use Phinx\Db\Table\Index;
use Phinx\Migration\IrreversibleMigrationException;
use PHPUnit\Framework\TestCase;

class ProxyAdapterTest extends TestCase
{
    /**
     * @var \Phinx\Db\Adapter\ProxyAdapter
     */
    private $adapter;

    public function setUp(): void
    {
        $stub = $this->getMockBuilder('\Phinx\Db\Adapter\PdoAdapter')
            ->setConstructorArgs([[]])
            ->getMock();

        $this->adapter = new ProxyAdapter($stub);
    }

    public function tearDown(): void
    {
        unset($this->adapter);
    }

    public function testProxyAdapterCanInvertCreateTable()
    {
        $table = new \Phinx\Db\Table('atable');
        $this->adapter->createTable($table);

        $commands = $this->adapter->getInvertedCommands();
        $this->assertEquals('dropTable', $commands[0]['name']);
        $this->assertEquals('atable', $commands[0]['arguments'][0]);
    }

    public function testProxyAdapterCanInvertRenameTable()
    {
        $this->adapter->renameTable('oldname', 'newname');

        $commands = $this->adapter->getInvertedCommands();
        $this->assertEquals('renameTable', $commands[0]['name']);
        $this->assertEquals('newname', $commands[0]['arguments'][0]);
        $this->assertEquals('oldname', $commands[0]['arguments'][1]);
    }

    public function testProxyAdapterCanInvertAddColumn()
    {
        $table = new \Phinx\Db\Table('atable');
        $column = new \Phinx\Db\Table\Column();
        $column->setName('acolumn');

        $this->adapter->addColumn($table, $column);

        $commands = $this->adapter->getInvertedCommands();
        $this->assertEquals('dropColumn', $commands[0]['name']);
        $this->assertEquals('atable', $commands[0]['arguments'][0]);
        $this->assertStringContainsString('acolumn', $commands[0]['arguments'][1]);
    }

    public function testProxyAdapterCanInvertRenameColumn()
    {
        $this->adapter->renameColumn('atable', 'oldname', 'newname');

        $commands = $this->adapter->getInvertedCommands();
        $this->assertEquals('renameColumn', $commands[0]['name']);
        $this->assertEquals('atable', $commands[0]['arguments'][0]);
        $this->assertEquals('newname', $commands[0]['arguments'][1]);
        $this->assertEquals('oldname', $commands[0]['arguments'][2]);
    }

    public function testProxyAdapterCanInvertAddIndex()
    {
        $table = new \Phinx\Db\Table('atable');
        $index = new \Phinx\Db\Table\Index();
        $index->setType(\Phinx\Db\Table\Index::INDEX)
              ->setColumns(['email']);

        $this->adapter->addIndex($table, $index);

        $commands = $this->adapter->getInvertedCommands();
        $this->assertEquals('dropIndex', $commands[0]['name']);
        $this->assertEquals('atable', $commands[0]['arguments'][0]);
        $this->assertContains('email', $commands[0]['arguments'][1]);
    }

    public function testProxyAdapterCanInvertAddForeignKey()
    {
        $table = new \Phinx\Db\Table('atable');
        $refTable = new \Phinx\Db\Table('refTable');
        $fk = new \Phinx\Db\Table\ForeignKey();
        $fk->setReferencedTable($refTable)
           ->setColumns(['ref_table_id'])
           ->setReferencedColumns(['id']);

        $this->adapter->addForeignKey($table, $fk);

        $commands = $this->adapter->getInvertedCommands();
        $this->assertEquals('dropForeignKey', $commands[0]['name']);
        $this->assertEquals('atable', $commands[0]['arguments'][0]);
        $this->assertContains('ref_table_id', $commands[0]['arguments'][1]);
    }

    /**
     * @expectedException \Phinx\Migration\IrreversibleMigrationException
     * @expectedExceptionMessage Cannot reverse a "createDatabase" command
     */
    public function testGetInvertedCommandsThrowsExceptionForIrreversibleCommand()
    {
        $this->adapter->recordCommand('createDatabase', ['testdb']);
        $this->adapter->getInvertedCommands();
    }
}
