<?php

namespace Test\Phinx\Db\Adapter;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Phinx\Config\Config;
use Phinx\Db\Adapter\PdoAdapter;
use RuntimeException;
use PHPUnit\Framework\TestCase;

class PdoAdapterTestPDOMock extends PDO
{
    public function __construct()
    {
    }
}

/**
 * A mock PDO that stores its last exec()'d SQL that can be retrieved for queries.
 *
 * This exists as $this->getMockForAbstractClass('\PDO') fails under PHP5.4 and
 * an older PHPUnit; a PDO instance cannot be serialised.
 */
class PdoAdapterTestPDOMockWithExecChecks extends PdoAdapterTestPDOMock
{
    private string $sql;

    public function exec(string $sql): int|false
    {
        $this->sql = $sql;

        return 1;
    }

    public function getExecutedSqlForTest(): string
    {
        return $this->sql;
    }
}

class ConcretePdoAdapter extends PdoAdapter
{
    protected function ensureSchemaTableExistsAndCorrect(): void
    {
    }

    public function getAdapterType()
    {
        return 'concrete';
    }

    public function hasTransactions()
    {
        return true;
    }

    public function beginTransaction()
    {
    }

    public function commitTransaction()
    {
    }

    public function rollbackTransaction()
    {
    }

    public function quoteTableName($tableName)
    {
        return '`' . $tableName . '`';
    }

    public function quoteColumnName($columnName)
    {
        return '`' . $columnName . '`';
    }
}

class PdoAdapterTest extends TestCase
{
    private $adapter;

    public function setUp(): void
    {
        $this->adapter = new ConcretePdoAdapter(['foo' => 'bar']);
    }

    public function tearDown(): void
    {
        unset($this->adapter);
    }

    public function testOptions()
    {
        $options = $this->adapter->getOptions();
        $this->assertArrayHasKey('foo', $options);
        $this->assertEquals('bar', $options['foo']);
    }

    public function testOptionsSetConnection()
    {
        $connection = new PdoAdapterTestPDOMock();
        $this->adapter->setOptions(['connection' => $connection]);

        $this->assertSame($connection, $this->adapter->getConnection());
    }

    public function testOptionsSetSchemaTableName()
    {
        $this->assertEquals('phinxlog', $this->adapter->getSchemaTableName());
        $this->adapter->setOptions(['default_migration_table' => 'schema_table_test']);
        $this->assertEquals('schema_table_test', $this->adapter->getSchemaTableName());
    }

    public function testSchemaTableName()
    {
        $this->assertEquals('phinxlog', $this->adapter->getSchemaTableName());
        $this->adapter->setSchemaTableName('schema_table_test');
        $this->assertEquals('schema_table_test', $this->adapter->getSchemaTableName());
    }

    #[DataProvider('getVersionLogDataProvider')]
    public function testGetVersionLog($versionOrder, $expectedOrderBy)
    {
        $adapter = $this->getMockBuilder(ConcretePdoAdapter::class)
            ->setConstructorArgs([['version_order' => $versionOrder]])
            ->onlyMethods(['fetchAll', 'getSchemaTableName'])
            ->getMock();

        $schemaTableName = 'log';
        $adapter->expects($this->once())
            ->method('getSchemaTableName')
            ->willReturn($schemaTableName);

        $mockRows = [
            [
                'version' => '20120508120534',
                'key' => 'value'
            ],
            [
                'version' => '20130508120534',
                'key' => 'value'
            ],
        ];

        $adapter->expects($this->once())
            ->method('fetchAll')
            ->with("SELECT * FROM $schemaTableName ORDER BY $expectedOrderBy")
            ->willReturn($mockRows);

        // we expect the mock rows but indexed by version creation time
        $expected = [
            '20120508120534' => [
                'version' => '20120508120534',
                'key' => 'value'
            ],
            '20130508120534' => [
                'version' => '20130508120534',
                'key' => 'value'
            ],
        ];

        $this->assertEquals($expected, $adapter->getVersionLog());
    }

    public static function getVersionLogDataProvider()
    {
        return [
            'With Creation Time Version Order' => [
                Config::VERSION_ORDER_CREATION_TIME, 'version ASC'
            ],
            'With Execution Time Version Order' => [
                Config::VERSION_ORDER_EXECUTION_TIME, 'start_time ASC, version ASC'
            ],
        ];
    }

    public function testGetVersionLogInvalidVersionOrderKO()
    {
        $adapter = new ConcretePdoAdapter(['version_order' => 'invalid']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Invalid version_order configuration option');
        $adapter->getVersionLog();
    }

    /**
     * Tests that execute() can be called on the adapter, and that the SQL is passed through to the PDO.
     */
    public function testExecuteCanBeCalled()
    {
        $pdo = new PdoAdapterTestPDOMockWithExecChecks();

        $this->adapter->setConnection($pdo);

        $this->adapter->execute('SELECT 1');

        $this->assertSame('SELECT 1', $pdo->getExecutedSqlForTest());
    }
}
