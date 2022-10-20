<?php

namespace Test\Phinx\Migration;

use PHPUnit\Framework\TestCase;

class AbstractMigrationTest extends TestCase
{
    public function testUp()
    {
        // stub migration
        $migrationStub = $this->getMockForAbstractClass('\Phinx\Migration\AbstractMigration', [0]);
        $this->assertNull($migrationStub->up());
    }

    public function testAdapterMethods()
    {
        // stub migration
        $migrationStub = $this->getMockForAbstractClass('\Phinx\Migration\AbstractMigration', [0]);

        // stub adapter
        $adapterStub = $this->getMockBuilder('\Phinx\Db\Adapter\PdoAdapter')
            ->setConstructorArgs([[]])
            ->getMock();

        // test methods
        $this->assertNull($migrationStub->getAdapter());
        $migrationStub->setAdapter($adapterStub);
        $this->assertInstanceOf(
            'Phinx\Db\Adapter\AdapterInterface',
            $migrationStub->getAdapter()
        );
    }

    public function testSetOutputMethods()
    {
        // stub migration
        $migrationStub = $this->getMockForAbstractClass('\Phinx\Migration\AbstractMigration', [0]);

        // stub output
        $outputStub = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')->getMock();

        // test methods
        $this->assertNull($migrationStub->getOutput());
        $migrationStub->setOutput($outputStub);
        $this->assertInstanceOf('\Symfony\Component\Console\Output\OutputInterface', $migrationStub->getOutput());
    }

    public function testGetInputMethodWithInjectedInput()
    {
        // stub input
        $inputStub = $this->getMockBuilder('\Symfony\Component\Console\Input\InputInterface')->getMock();

        // stub migration
        $migrationStub = $this->getMockForAbstractClass('\Phinx\Migration\AbstractMigration', [0, $inputStub, NULL]);

        // test methods
        $this->assertNotNull($migrationStub->getInput());
        $this->assertInstanceOf('\Symfony\Component\Console\Input\InputInterface', $migrationStub->getInput());
    }

    public function testGetOutputMethodWithInjectedOutput()
    {
        // stub output
        $outputStub = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')->getMock();

        // stub migration
        $migrationStub = $this->getMockForAbstractClass('\Phinx\Migration\AbstractMigration', [0, NULL, $outputStub]);

        // test methods
        $this->assertNotNull($migrationStub->getOutput());
        $this->assertInstanceOf('\Symfony\Component\Console\Output\OutputInterface', $migrationStub->getOutput());
    }

    public function testGetName()
    {
        $migrationStub = $this->getMockForAbstractClass('\Phinx\Migration\AbstractMigration', [0]);
        $this->assertStringContainsString('AbstractMigration', $migrationStub->getName());
    }

    public function testVersionMethods()
    {
        $migrationStub = $this->getMockForAbstractClass('\Phinx\Migration\AbstractMigration', [20120103080000]);
        $this->assertEquals(20120103080000, $migrationStub->getVersion());
        $migrationStub->setVersion(20120915093312);
        $this->assertEquals(20120915093312, $migrationStub->getVersion());
    }

    public function testExecute()
    {
        // stub migration
        $migrationStub = $this->getMockForAbstractClass('\Phinx\Migration\AbstractMigration', [0]);

        // stub adapter
        $adapterStub = $this->getMockBuilder('\Phinx\Db\Adapter\PdoAdapter')
            ->setConstructorArgs([[]])
            ->getMock();
        $adapterStub->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(2));

        $migrationStub->setAdapter($adapterStub);
        $this->assertEquals(2, $migrationStub->execute('SELECT FOO FROM BAR'));
    }

    public function testQuery()
    {
        // stub migration
        $migrationStub = $this->getMockForAbstractClass('\Phinx\Migration\AbstractMigration', [0]);

        // stub adapter
        $adapterStub = $this->getMockBuilder('\Phinx\Db\Adapter\PdoAdapter')
            ->setConstructorArgs([[]])
            ->getMock();
        $adapterStub->expects($this->once())
            ->method('query')
            ->will($this->returnValue([['0' => 'bar', 'foo' => 'bar']]));

        $migrationStub->setAdapter($adapterStub);
        $this->assertEquals([['0' => 'bar', 'foo' => 'bar']], $migrationStub->query('SELECT FOO FROM BAR'));
    }

    public function testFetchRow()
    {
        // stub migration
        $migrationStub = $this->getMockForAbstractClass('\Phinx\Migration\AbstractMigration', [0]);

        // stub adapter
        $adapterStub = $this->getMockBuilder('\Phinx\Db\Adapter\PdoAdapter')
            ->setConstructorArgs([[]])
            ->getMock();
        $adapterStub->expects($this->once())
            ->method('fetchRow')
            ->will($this->returnValue(['0' => 'bar', 'foo' => 'bar']));

        $migrationStub->setAdapter($adapterStub);
        $this->assertEquals(['0' => 'bar', 'foo' => 'bar'], $migrationStub->fetchRow('SELECT FOO FROM BAR'));
    }

    public function testFetchAll()
    {
        // stub migration
        $migrationStub = $this->getMockForAbstractClass('\Phinx\Migration\AbstractMigration', [0]);

        // stub adapter
        $adapterStub = $this->getMockBuilder('\Phinx\Db\Adapter\PdoAdapter')
            ->setConstructorArgs([[]])
            ->getMock();
        $adapterStub->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue([['0' => 'bar', 'foo' => 'bar']]));

        $migrationStub->setAdapter($adapterStub);
        $this->assertEquals([['0' => 'bar', 'foo' => 'bar']], $migrationStub->fetchAll('SELECT FOO FROM BAR'));
    }

    public function providerInsert()
    {
        $row1 = ['id' => 1, 'foo' => 'bar', 'bill' => 'baz'];
        $row2 = ['id' => 2, 'foo' => 'boop', 'bill' => 'bonk'];
        $row3 = ['id' => 3, 'foo' => 'barne', 'bill' => 'scs'];

        return [
            'single row'    => [
                'sometable',
                $row1,
                ['sometable', [$row1]],
            ],
            'multiple rows' => [
                'othertable',
                [$row1, $row2, $row3],
                ['othertable', [$row1, $row2, $row3]],
            ],
        ];
    }

    /**
     * @dataProvider providerInsert
     */
    public function testInsertCanPerformInserts($table, $data, $expectInsertParams)
    {
        // stub migration
        $migrationStub = $this->getMockForAbstractClass('\Phinx\Migration\AbstractMigration', [0]);

        // stub adapter
        $adapterStub = $this->getMockBuilder('\Phinx\Db\Adapter\PdoAdapter')
            ->setConstructorArgs([[]])
            ->getMock();
        $adapterStub->expects($this->once())
            ->method('bulkinsert')
            ->with(...$expectInsertParams);

        $migrationStub->setAdapter($adapterStub);
        $migrationStub->insert($table, $data);
    }

    public function testInsertThrowsWithVariableSchema()
    {
        $migrationStub = $this->getMockForAbstractClass('\Phinx\Migration\AbstractMigration', [0]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('mixed column keys');

        $migrationStub->insert(
            'sometable',
            [
                ['id' => 1, 'boo' => 'bar'],
                ['id' => 2, 'boo' => 'foo'],
                ['id' => 3, 'different_column' => 'foo'],
            ]
        );
    }

}
