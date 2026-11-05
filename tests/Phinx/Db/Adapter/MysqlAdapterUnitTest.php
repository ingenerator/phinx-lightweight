<?php

namespace Test\Phinx\Db\Adapter;

use PDO;
use PDOStatement;
use Phinx\Db\Adapter\MysqlAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use function defined;

class PDOMock extends PDO
{
    public function __construct()
    {
    }
}

class MysqlAdapterTester extends MysqlAdapter
{
    public function setMockConnection($connection)
    {
        $this->connection = $connection;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function getConnectionOrNull(): ?PDO
    {
        return $this->connection;
    }

}

class MysqlAdapterUnitTest extends TestCase
{
    /**
     * @var MysqlAdapterTester
     */
    private $adapter;

    private $conn;

    private $result;

    public function setUp(): void
    {
        if ( ! defined('MYSQL_DB_CONFIG')) {
            $this->markTestSkipped('Mysql tests disabled. See MYSQL_DB_CONFIG constant.');
        }

        $this->adapter = new MysqlAdapterTester([], new ArrayInput([]), new NullOutput());
    }

    private function getConn(): PDOMock
    {
        if ($this->conn === null) {
            $this->conn = $this->createMock(PDOMock::class);
            $this->adapter->setMockConnection($this->conn);
        }

        return $this->conn;
    }

    private function getResult(): PDOStatement
    {
        if ($this->result === null) {
            $this->result = $this->createMock(PDOStatement::class);
        }

        return $this->result;
    }

    // helper methods for easy mocking
    private function assertExecuteSql($expected_sql)
    {
        $this->getConn()->expects($this->once())
            ->method('exec')
            ->with($this->equalTo($expected_sql))
            ->willReturn(1);
    }

    private function assertQuerySql($expectedSql, $returnValue = NULL)
    {
        $expect = $this->getConn()->expects($this->once())
            ->method('query')
            ->with($this->equalTo($expectedSql));
        if ( ! is_null($returnValue)) {
            $expect->willReturn($returnValue);
        }
    }

    private function assertFetchRowSql($expectedSql, $returnValue)
    {
        $this->getResult()->expects($this->once())
            ->method('fetch')
            ->willReturn($returnValue);
        $this->assertQuerySql($expectedSql, $this->getResult());
    }

    public function testDisconnect()
    {
        $this->adapter->setMockConnection($this->createStub(PDOMock::class));
        $this->assertNotNull($this->adapter->getConnection());
        $this->adapter->disconnect();
        $this->assertNull($this->adapter->getConnectionOrNull());
    }

    public function testHasTransactions()
    {
        $this->assertTrue($this->adapter->hasTransactions());
    }

    public function testBeginTransaction()
    {
        $this->assertExecuteSql("START TRANSACTION");
        $this->adapter->beginTransaction();
    }

    public function testCommitTransaction()
    {
        $this->assertExecuteSql("COMMIT");
        $this->adapter->commitTransaction();
    }

    public function testRollbackTransaction()
    {
        $this->assertExecuteSql("ROLLBACK");
        $this->adapter->rollbackTransaction();
    }

    public function testHasTableExists()
    {
        $this->adapter->setOptions(['name' => 'database_name']);
        $this->getResult()->expects($this->once())
            ->method('fetch')
            ->willReturn(['somecontent']);
        $expectedSql = 'SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = \'database_name\' AND TABLE_NAME = \'table_name\'';
        $this->assertQuerySql($expectedSql, $this->getResult());
        $this->assertTrue($this->adapter->hasTable("table_name"));
    }

    public function testHasTableNotExists()
    {
        $this->adapter->setOptions(['name' => 'database_name']);
        $this->getResult()->expects($this->once())
            ->method('fetch')
            ->willReturn([]);
        $expectedSql = 'SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = \'database_name\' AND TABLE_NAME = \'table_name\'';
        $this->assertQuerySql($expectedSql, $this->getResult());
        $this->assertFalse($this->adapter->hasTable("table_name"));
    }

}
