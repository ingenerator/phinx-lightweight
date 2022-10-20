<?php

namespace Test\Phinx\Db\Adapter;

use Phinx\Db\Adapter\MysqlAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use const MYSQL_DB_CONFIG;

class MysqlAdapterTest extends TestCase
{
    /**
     * @var \Phinx\Db\Adapter\MysqlAdapter
     */
    private $adapter;

    public function setUp(): void
    {
        if (!defined('MYSQL_DB_CONFIG')) {
            $this->markTestSkipped('Mysql tests disabled.');
        }

        $this->adapter = new MysqlAdapter(MYSQL_DB_CONFIG, new ArrayInput([]), new NullOutput());

        // ensure the database is empty for each test
        $database_name = MYSQL_DB_CONFIG['name'];
        $this->adapter->execute("DROP DATABASE IF EXISTS `$database_name`;");
        $this->adapter->execute("CREATE DATABASE `$database_name`;");

        // leave the adapter in a disconnected state for each test
        $this->adapter->disconnect();
    }

    public function tearDown(): void
    {
        unset($this->adapter);
    }

    public function testConnection()
    {
        $this->assertInstanceOf('PDO', $this->adapter->getConnection());
    }

    public function testConnectionWithoutPort()
    {
        $this->markTestSkipped('MySQL is on a non-standard port. Testing without port is going to result in failure');
        $options = $this->adapter->getOptions();
        unset($options['port']);
        $this->adapter->setOptions($options);
        $this->assertInstanceOf('PDO', $this->adapter->getConnection());
    }

    public function testConnectionWithInvalidCredentials()
    {
        $options = [
            'host' => MYSQL_DB_CONFIG['host'],
            'name' => MYSQL_DB_CONFIG['name'],
            'port' => MYSQL_DB_CONFIG['port'],
            'user' => 'invaliduser',
            'pass' => 'invalidpass'
        ];

        try {
            $adapter = new MysqlAdapter($options, new ArrayInput([]), new NullOutput());
            $adapter->connect();
            $this->fail('Expected the adapter to throw an exception');
        } catch (\InvalidArgumentException $e) {
            $this->assertInstanceOf(
                'InvalidArgumentException',
                $e,
                'Expected exception of type InvalidArgumentException, got ' . get_class($e)
            );
            $this->assertMatchesRegularExpression('/There was a problem connecting to the database/', $e->getMessage());
        }
    }

    public function testConnectionWithSocketConnection()
    {
        $this->markTestSkipped('MySQL socket connection skipped. We don\'t support UNIX sockets.');

        $options = [
            'name' => MYSQL_DB_CONFIG['name'],
            'user' => MYSQL_DB_CONFIG['user'],
            'pass' => MYSQL_DB_CONFIG['pass'],
            'unix_socket' => '',
        ];

        $adapter = new MysqlAdapter($options, new ArrayInput([]), new NullOutput());
        $adapter->connect();

        $this->assertInstanceOf('\PDO', $this->adapter->getConnection());
    }

    public function testCreatingTheSchemaTableOnConnect()
    {
        $schema_table_name = $this->adapter->getSchemaTableName();
        $this->adapter->connect();
        $this->assertTrue($this->adapter->hasTable($this->adapter->getSchemaTableName()));
        $this->adapter->execute("DROP TABLE `$schema_table_name`;");
        $this->assertFalse($this->adapter->hasTable($this->adapter->getSchemaTableName()));
        $this->adapter->disconnect();
        $this->adapter->connect();
        $this->assertTrue($this->adapter->hasTable($this->adapter->getSchemaTableName()));
    }

    public function testSchemaTableIsCreatedWithPrimaryKey()
    {
        $this->adapter->connect();
        $table = new \Phinx\Db\Table($this->adapter->getSchemaTableName(), [], $this->adapter);
        $this->assertTrue($this->adapter->hasIndex($this->adapter->getSchemaTableName(), ['version']));
    }

    public function testQuoteTableName()
    {
        $this->assertEquals('`test_table`', $this->adapter->quoteTableName('test_table'));
    }

    public function testQuoteColumnName()
    {
        $this->assertEquals('`test_column`', $this->adapter->quoteColumnName('test_column'));
    }

    public function testBulkInsertData()
    {
        $this->adapter->execute(<<<SQL
            CREATE TABLE `table1`(
                `column1` VARCHAR(255),
                `column2` INT,
                `column3` VARCHAR(255) NOT NULL DEFAULT 'test'
            );
            SQL
        );
        $data = [
            [
                'column1' => 'value1',
                'column2' => 1,
            ],
            [
                'column1' => 'value2',
                'column2' => 2,
            ],
            [
                'column1' => 'value3',
                'column2' => 3,
            ]
        ];

        $this->adapter->bulkinsert('table1', $data);

        $rows = $this->adapter->fetchAll('SELECT * FROM table1');
        $this->assertEquals('value1', $rows[0]['column1']);
        $this->assertEquals('value2', $rows[1]['column1']);
        $this->assertEquals('value3', $rows[2]['column1']);
        $this->assertEquals(1, $rows[0]['column2']);
        $this->assertEquals(2, $rows[1]['column2']);
        $this->assertEquals(3, $rows[2]['column2']);
        $this->assertEquals('test', $rows[0]['column3']);
        $this->assertEquals('test', $rows[2]['column3']);
    }

    public function testInsertData()
    {
        $this->adapter->execute(<<<SQL
            CREATE TABLE `table1`(
                `column1` VARCHAR(255),
                `column2` INT,
                `column3` VARCHAR(255) NOT NULL DEFAULT 'test'
            );
            SQL
        );

        $data = [
            [
                'column1' => 'value1',
                'column2' => 1,
            ],
            [
                'column1' => 'value2',
                'column2' => 2,
            ],
            [
                'column1' => 'value3',
                'column2' => 3,
                'column3' => 'foo',
            ]
        ];

        $this->adapter->insert('table1', $data[0]);
        $this->adapter->insert('table1', $data[1]);
        $this->adapter->insert('table1', $data[2]);

        $rows = $this->adapter->fetchAll('SELECT * FROM table1');
        $this->assertEquals('value1', $rows[0]['column1']);
        $this->assertEquals('value2', $rows[1]['column1']);
        $this->assertEquals('value3', $rows[2]['column1']);
        $this->assertEquals(1, $rows[0]['column2']);
        $this->assertEquals(2, $rows[1]['column2']);
        $this->assertEquals(3, $rows[2]['column2']);
        $this->assertEquals('test', $rows[0]['column3']);
        $this->assertEquals('foo', $rows[2]['column3']);
    }

}
