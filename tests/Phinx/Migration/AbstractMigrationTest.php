<?php

namespace Test\Phinx\Migration;

use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use InvalidArgumentException;
use PDO;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\PdoAdapter;
use Phinx\Migration\AbstractMigration;
use PHPUnit\Framework\TestCase;

class ConcreteAbstractMigration extends AbstractMigration
{
    public function up(): void
    {
    }
}

class AbstractMigrationTest extends TestCase
{
    public function testUp()
    {
        // stub migration
        $migrationStub = new ConcreteAbstractMigration(0);
        $this->assertNull($migrationStub->up());
    }

    public function testAdapterMethods()
    {
        // stub migration
        $migrationStub = new ConcreteAbstractMigration(0);

        // stub adapter
        $adapterStub = $this->createStub(PdoAdapter::class);

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
        $migrationStub = new ConcreteAbstractMigration(0);

        // stub output
        $outputStub = $this->createStub('\Symfony\Component\Console\Output\OutputInterface');

        // test methods
        $this->assertNull($migrationStub->getOutput());
        $migrationStub->setOutput($outputStub);
        $this->assertInstanceOf('\Symfony\Component\Console\Output\OutputInterface', $migrationStub->getOutput());
    }

    public function testGetInputMethodWithInjectedInput()
    {
        // stub input
        $inputStub = $this->createStub('\Symfony\Component\Console\Input\InputInterface');

        // stub migration
        $migrationStub = new ConcreteAbstractMigration(0, $inputStub, NULL);

        // test methods
        $this->assertNotNull($migrationStub->getInput());
        $this->assertInstanceOf('\Symfony\Component\Console\Input\InputInterface', $migrationStub->getInput());
    }

    public function testGetOutputMethodWithInjectedOutput()
    {
        // stub output
        $outputStub = $this->createStub('\Symfony\Component\Console\Output\OutputInterface');

        // stub migration
        $migrationStub = new ConcreteAbstractMigration(0, NULL, $outputStub);

        // test methods
        $this->assertNotNull($migrationStub->getOutput());
        $this->assertInstanceOf('\Symfony\Component\Console\Output\OutputInterface', $migrationStub->getOutput());
    }

    public function testGetName()
    {
        $migrationStub = new ConcreteAbstractMigration(0);
        $this->assertStringContainsString('AbstractMigration', $migrationStub->getName());
    }

    public function testVersionMethods()
    {
        $migrationStub = new ConcreteAbstractMigration(20120103080000);
        $this->assertEquals(20120103080000, $migrationStub->getVersion());
        $migrationStub->setVersion(20120915093312);
        $this->assertEquals(20120915093312, $migrationStub->getVersion());
    }

    public function testExecute()
    {
        // stub migration
        $migrationStub = new ConcreteAbstractMigration(0);

        // stub adapter
        $adapterStub = $this->createMock(PdoAdapter::class);
        $adapterStub->expects($this->once())
            ->method('execute')
            ->willReturn(2);

        $migrationStub->setAdapter($adapterStub);
        $this->assertEquals(2, $migrationStub->execute('SELECT FOO FROM BAR'));
    }

    public function testQuery()
    {
        // stub migration
        $migrationStub = new ConcreteAbstractMigration(0);

        $queryStub = new class extends PDOStatement {};

        // stub adapter
        $adapterStub = $this->createMock(PdoAdapter::class);
        $adapterStub->expects($this->once())
            ->method('query')
            ->willReturn($queryStub);

        $migrationStub->setAdapter($adapterStub);
        $this->assertEquals($queryStub, $migrationStub->query('SELECT FOO FROM BAR'));
    }

    public function testFetchRow()
    {
        // stub migration
        $migrationStub = new ConcreteAbstractMigration(0);

        // stub adapter
        $adapterStub = $this->createMock(PdoAdapter::class);
        $adapterStub->expects($this->once())
            ->method('fetchRow')
            ->willReturn(['0' => 'bar', 'foo' => 'bar']);

        $migrationStub->setAdapter($adapterStub);
        $this->assertEquals(['0' => 'bar', 'foo' => 'bar'], $migrationStub->fetchRow('SELECT FOO FROM BAR'));
    }

    public function testFetchAll()
    {
        // stub migration
        $migrationStub = new ConcreteAbstractMigration(0);

        // stub adapter
        $adapterStub = $this->createMock(PdoAdapter::class);
        $adapterStub->expects($this->once())
            ->method('fetchAll')
            ->willReturn([['0' => 'bar', 'foo' => 'bar']]);

        $migrationStub->setAdapter($adapterStub);
        $this->assertEquals([['0' => 'bar', 'foo' => 'bar']], $migrationStub->fetchAll('SELECT FOO FROM BAR'));
    }

    public static function providerInsert()
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

    #[DataProvider('providerInsert')]
    public function testInsertCanPerformInserts($table, $data, $expectInsertParams)
    {
        // stub migration
        $migrationStub = new ConcreteAbstractMigration(0);

        // stub adapter
        $adapterStub = $this->createMock(PdoAdapter::class);
        $adapterStub->expects($this->once())
            ->method('bulkinsert')
            ->with(...$expectInsertParams);

        $migrationStub->setAdapter($adapterStub);
        $migrationStub->insert($table, $data);
    }

    public function testInsertThrowsWithVariableSchema()
    {
        $migrationStub = new ConcreteAbstractMigration(0);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('mixed column keys');

        $migrationStub->insert(
            'sometable',
            [
                ['id' => 1, 'boo' => 'bar'],
                ['id' => 2, 'boo' => 'foo'],
                ['id' => 3, 'different_column' => 'foo'],
            ]
        );
    }

    public function testPrepareAndExecute()
    {
        $migrationStub = new ConcreteAbstractMigration(0);

        $pdoStub = $this->createMock(PDO::class);

        $statementStub = $this->createMock(PDOStatement::class);


        $pdoStub->expects($this->once())
            ->method('prepare')
            ->with('UPDATE foo SET bar = :bax WHERE id=:id')
            ->willReturn($statementStub);

        $statementStub->expects($this->once())
            ->method('execute')
            ->with(['bax' => 'any', 'id' => 15]);

        $migrationStub->setAdapter(
            new class ($pdoStub) extends MysqlAdapter {
                public function __construct(PDO $connection) { $this->connection = $connection; }
            }
        );

        $migrationStub->prepareAndExecute('UPDATE foo SET bar = :bax WHERE id=:id', ['bax' => 'any', 'id' => 15]);
    }

}
