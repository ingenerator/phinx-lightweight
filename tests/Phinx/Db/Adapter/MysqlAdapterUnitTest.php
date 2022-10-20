<?php

namespace Test\Phinx\Db\Adapter;

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Table\Column;
use Phinx\Db\Table\Index;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use function defined;

class PDOMock extends \PDO
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

    public function getConnection()
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
        if (!defined('MYSQL_DB_CONFIG')) {
            $this->markTestSkipped('Mysql tests disabled. See MYSQL_DB_CONFIG constant.');
        }

        $this->adapter = new MysqlAdapterTester([], new ArrayInput([]), new NullOutput());

        $this->conn = $this->getMockBuilder('PDOMock')
                           ->disableOriginalConstructor()
                           ->setMethods([ 'query', 'exec', 'quote' ])
                           ->getMock();
        $this->result = $this->getMockBuilder('stdclass')
                             ->disableOriginalConstructor()
                             ->setMethods([ 'fetch' ])
                             ->getMock();
        $this->adapter->setMockConnection($this->conn);
    }

    // helper methods for easy mocking
    private function assertExecuteSql($expected_sql)
    {
        $this->conn->expects($this->once())
                   ->method('exec')
                   ->with($this->equalTo($expected_sql));
    }

    private function assertQuerySql($expectedSql, $returnValue = null)
    {
        $expect = $this->conn->expects($this->once())
                       ->method('query')
                       ->with($this->equalTo($expectedSql));
        if (!is_null($returnValue)) {
            $expect->will($this->returnValue($returnValue));
        }
    }

    private function assertFetchRowSql($expectedSql, $returnValue)
    {
        $this->result->expects($this->once())
                     ->method('fetch')
                     ->will($this->returnValue($returnValue));
        $this->assertQuerySql($expectedSql, $this->result);
    }

    public function testDisconnect()
    {
        $this->assertNotNull($this->adapter->getConnection());
        $this->adapter->disconnect();
        $this->assertNull($this->adapter->getConnection());
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
        $this->result->expects($this->once())
                     ->method('fetch')
                     ->will($this->returnValue(['somecontent']));
        $expectedSql = 'SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = \'database_name\' AND TABLE_NAME = \'table_name\'';
        $this->assertQuerySql($expectedSql, $this->result);
        $this->assertTrue($this->adapter->hasTable("table_name"));
    }

    public function testHasTableNotExists()
    {
        $this->adapter->setOptions(['name' => 'database_name']);
        $this->result->expects($this->once())
                     ->method('fetch')
                     ->will($this->returnValue([]));
        $expectedSql = 'SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = \'database_name\' AND TABLE_NAME = \'table_name\'';
        $this->assertQuerySql($expectedSql, $this->result);
        $this->assertFalse($this->adapter->hasTable("table_name"));
    }

}
