<?php

namespace Test\Phinx\Util;

use org\bovigo\vfs\vfsStream;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Util\SqlDumpImporter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class SqlDumpImporterTest extends TestCase
{

    private AdapterInterface $adapter;

    private OutputInterface $output;

    public function providerStatements()
    {
        return [
            'single statement, no terminator'                                => [
                <<<SQL
                CREATE TABLE `foo` (
                    `whatever` INT NOT NULL
                ) ENGINE=INNODB
                SQL,
                [
                    <<<SQL
                    CREATE TABLE `foo` (
                        `whatever` INT NOT NULL
                    ) ENGINE=INNODB
                    SQL,
                ],
            ],
            'multiple statements more like a dump file'                      => [
                <<<SQL
                /*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
                /*!40103 SET TIME_ZONE='+00:00' */;
                /*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
                /*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
                /*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
                /*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
                /*!40101 SET @saved_cs_client     = @@character_set_client */;
                /*!40101 SET character_set_client = utf8 */;
                CREATE TABLE `customers` (
                  `id` int(11) NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
                /*!40101 SET character_set_client = @saved_cs_client */;
                SQL,
                [
                    '/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */',
                    '/*!40103 SET TIME_ZONE=\'+00:00\' */',
                    '/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */',
                    '/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */',
                    '/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=\'NO_AUTO_VALUE_ON_ZERO\' */',
                    '/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */',
                    '/*!40101 SET @saved_cs_client     = @@character_set_client */',
                    '/*!40101 SET character_set_client = utf8 */',
                    <<<SQL
                    CREATE TABLE `customers` (
                      `id` int(11) NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
                    SQL,
                    '/*!40101 SET character_set_client = @saved_cs_client */;',
                ],
            ],
            'comments and empty lines are included with following statement' => [
                <<<SQL
                /*!40101 SET character_set_client = @saved_cs_client */;

                --
                -- Table structure for table `customers`
                --

                /*!40101 SET @saved_cs_client     = @@character_set_client */;
                /*!40101 SET character_set_client = utf8 */;
                SQL,
                [
                    '/*!40101 SET character_set_client = @saved_cs_client */',
                    <<<SQL

                    --
                    -- Table structure for table `customers`
                    --

                    /*!40101 SET @saved_cs_client     = @@character_set_client */
                    SQL,
                    '/*!40101 SET character_set_client = utf8 */;',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerStatements
     */
    public function testItExecutesEachStatementIndividually($content, $expectStatements)
    {
        $path          = $this->givenSqlFileWithContent($content);
        $this->adapter = new class extends MysqlAdapter {
            public array $executed = [];

            public function __construct() { }

            public function execute(string $sql): false|int
            {
                $this->executed[] = $sql;

                return 0;
            }
        };

        $this->newSubject()->import($path);

        $this->assertSame($expectStatements, $this->adapter->executed);
    }

    public function testItIsInitialisable()
    {
        $this->assertInstanceOf(SqlDumpImporter::class, $this->newSubject());
    }

    private function newSubject(): SqlDumpImporter
    {
        return new SqlDumpImporter($this->adapter, $this->output);
    }

    public function testItLogsFailingStatementAndRethrows()
    {
        $path          = $this->givenSqlFileWithContent(
            <<<SQL
            /*!40101 SET @saved_cs_client     = @@character_set_client */;
            /*!40101 SET character_set_client = utf8 */;

            CREATE TABLE `foo` (
                `column` BADTYPE JUNK DEFINITION
            ) ENGINE=nothing;

            /*!40101 SET character_set_client = utf8 */;
            SQL
        );
        $mockException = new \RuntimeException('SQLSTATE Whatever');
        $this->adapter = new class($mockException) extends MysqlAdapter {
            public function __construct(private \Exception $exception) { }

            public function execute(string $sql): false|int
            {
                if (\str_contains($sql, 'BADTYPE')) {
                    throw $this->exception;
                }

                return 0;
            }
        };
        $this->output  = new BufferedOutput(decorated: FALSE);

        try {
            $this->newSubject()->import($path);
            $this->fail('Expected exception, none got');
        } catch (\RuntimeException$e) {
            $this->assertSame($e, $mockException, 'Expected exception to be rethrown');
        }

        $this->assertSame(
            <<<LOG
            Failed importing $path - failing statement #3:

            CREATE TABLE `foo` (
                `column` BADTYPE JUNK DEFINITION
            ) ENGINE=nothing

            LOG,
            $this->output->fetch()
        );
    }

    public function testItThrowsIfFileIsEmpty()
    {
        $path = $this->givenSqlFileWithContent('');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is empty');

        $this->newSubject()->import($path);
    }

    private function givenSqlFileWithContent(string $fileContent): string
    {
        $vfs  = vfsStream::setup('import', NULL, [
            'some-sql.sql' => $fileContent,
        ]);
        $path = $vfs->getChild('some-sql.sql')->url();

        return $path;
    }

    public function testItThrowsIfFileNotExists()
    {
        $vfs               = vfsStream::setup('import');
        $missing_file_path = $vfs->url().'/some-file.sql';
        $subject           = $this->newSubject();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('some-file.sql');
        $subject->import($missing_file_path);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = $this->getMockBuilder(AdapterInterface::class)->getMock();
        $this->output  = new NullOutput;
    }

    public function testItThrowsIfStatementLineTooLong()
    {
        $path = $this->givenSqlFileWithContent(
            <<<SQL
            /*!40101 SET @saved_cs_client     = @@character_set_client */;
            /*!40101 SET character_set_client = utf8 */;

            INSERT INTO `whatever`
            (`foo`, `bar`)
            VALUES
            ('something long'),
            ('something else long'),
            ('something even longer');

            SELECT 'some short thing' FROM `whatever`;

            SQL
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Statement 3 was exactly the buffer size (110 bytes) - most likely it was truncated'
        );

        $this->newSubject()->import($path, max_statement_length: 110);
    }

    public function testItWritesRegularProgressAndSummaryAtEnd()
    {
        $queries      = \str_repeat("/* SELECT anything */;\n", 45);
        $path         = $this->givenSqlFileWithContent($queries);
        $this->output = new BufferedOutput;
        $this->newSubject()->import($path);

        $this->assertSame(
            <<<LOG
            ..
            45 statements executed from $path

            LOG,
            $this->output->fetch()
        );
    }

}
