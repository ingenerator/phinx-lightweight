<?php

namespace Test\Phinx\Migration;

use Phinx\Config\Config;
use Phinx\Migration\Manager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use function defined;
use const MYSQL_DB_CONFIG;

class ManagerTest extends TestCase
{
    /** @var Config */
    protected $config;

    /**
     * @var InputInterface $input
     */
    protected $input;

    /**
     * @var OutputInterface $output
     */
    protected $output;

    /**
     * @var Manager
     */
    private $manager;

    protected function setUp(): void
    {
        if (!defined('MYSQL_DB_CONFIG')) {
            $this->markTestSkipped('Mysql tests disabled. See MYSQL_DB_CONFIG constant.');
        }

        $this->config = new Config($this->getConfigArray());
        $this->input = new ArrayInput([]);
        $this->output = new StreamOutput(fopen('php://memory', 'a', false));
        $this->output->setDecorated(false);
        $this->manager = new Manager($this->config, $this->input, $this->output);
    }

    protected function getConfigWithNamespace($paths = [])
    {
        if (empty($paths)) {
            $paths = [
                'migrations' => [
                    'Foo\Bar' => $this->getCorrectedPath(__DIR__ . '/_files_foo_bar/migrations'),
                ],
                'seeds' => [
                    'Foo\Bar' => $this->getCorrectedPath(__DIR__ . '/_files_foo_bar/seeds'),
                ],
            ];
        }
        $config = clone $this->config;
        $config['paths'] = $paths;

        return $config;
    }

    protected function getConfigWithMixedNamespace($paths = [])
    {
        if (empty($paths)) {
            $paths = [
                'migrations' => [
                    $this->getCorrectedPath(__DIR__ . '/_files/migrations'),
                    'Baz' => $this->getCorrectedPath(__DIR__ . '/_files_baz/migrations'),
                    'Foo\Bar' => $this->getCorrectedPath(__DIR__ . '/_files_foo_bar/migrations'),
                ],
                'seeds' => [
                    $this->getCorrectedPath(__DIR__ . '/_files/seeds'),
                    'Baz' => $this->getCorrectedPath(__DIR__ . '/_files_baz/seeds'),
                    'Foo\Bar' => $this->getCorrectedPath(__DIR__ . '/_files_foo_bar/seeds'),
                ],
            ];
        }
        $config = clone $this->config;
        $config['paths'] = $paths;

        return $config;
    }

    protected function tearDown(): void
    {
        $this->manager = null;
    }

    private function getCorrectedPath($path)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Returns a sample configuration array for use with the unit tests.
     *
     * @return array
     */
    public function getConfigArray(): array
    {
        return [
            'paths' => [
                'migrations' => $this->getCorrectedPath(__DIR__ . '/_files/migrations'),
                'seeds' => $this->getCorrectedPath(__DIR__ . '/_files/seeds'),
            ],
            'environments' => [
                'default_migration_table' => 'phinxlog',
                'default_database' => 'production',
                'production' => [
                    'adapter' => 'mysql',
                    'host' => MYSQL_DB_CONFIG['host'],
                    'name' => MYSQL_DB_CONFIG['name'],
                    'user' => MYSQL_DB_CONFIG['user'],
                    'pass' => MYSQL_DB_CONFIG['pass'],
                    'port' => MYSQL_DB_CONFIG['port']
                ]
            ]
        ];
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(
            'Symfony\Component\Console\Output\StreamOutput',
            $this->manager->getOutput()
        );
    }

    public function testPrintStatusMethod()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $envStub->expects($this->once())
                ->method('getVersionLog')
                ->will($this->returnValue(
                    [
                        '20120111235330' =>
                            [
                                'version' => '20120111235330',
                                'start_time' => '2012-01-11 23:53:36',
                                'end_time' => '2012-01-11 23:53:37',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20120116183504' =>
                            [
                                'version' => '20120116183504',
                                'start_time' => '2012-01-16 18:35:40',
                                'end_time' => '2012-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                    ]
                ));

        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->getOutput()->setDecorated(false);
        $return = $this->manager->printStatus('mockenv');
        $this->assertEquals(0, $return);

        rewind($this->manager->getOutput()->getStream());
        $outputStr = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertMatchesRegularExpression('/up  20120111235330  2012-01-11 23:53:36  2012-01-11 23:53:37  TestMigration/', $outputStr);
        $this->assertMatchesRegularExpression('/up  20120116183504  2012-01-16 18:35:40  2012-01-16 18:35:41  TestMigration2/', $outputStr);
    }

    public function testPrintStatusMethodWithNamespace()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $envStub->expects($this->once())
                ->method('getVersionLog')
                ->will($this->returnValue(
                    [
                        '20160111235330' =>
                            [
                                'version' => '20160111235330',
                                'start_time' => '2016-01-11 23:53:36',
                                'end_time' => '2016-01-11 23:53:37',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20160116183504' =>
                            [
                                'version' => '20160116183504',
                                'start_time' => '2016-01-16 18:35:40',
                                'end_time' => '2016-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                    ]
                ));

        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->getOutput()->setDecorated(false);
        $this->manager->setConfig($this->getConfigWithNamespace());
        $return = $this->manager->printStatus('mockenv');
        $this->assertEquals(0, $return);

        rewind($this->manager->getOutput()->getStream());
        $outputStr = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertMatchesRegularExpression('/up  20160111235330  2016-01-11 23:53:36  2016-01-11 23:53:37  Foo\\\\Bar\\\\TestMigration/', $outputStr);
        $this->assertMatchesRegularExpression('/up  20160116183504  2016-01-16 18:35:40  2016-01-16 18:35:41  Foo\\\\Bar\\\\TestMigration2/', $outputStr);
    }

    public function testPrintStatusMethodWithMixedNamespace()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $envStub->expects($this->once())
                ->method('getVersionLog')
                ->will($this->returnValue(
                    [
                        '20120111235330' =>
                            [
                                'version' => '20120111235330',
                                'start_time' => '2012-01-11 23:53:36',
                                'end_time' => '2012-01-11 23:53:37',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20120116183504' =>
                            [
                                'version' => '20120116183504',
                                'start_time' => '2012-01-16 18:35:40',
                                'end_time' => '2012-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20150111235330' =>
                            [
                                'version' => '20150111235330',
                                'start_time' => '2015-01-11 23:53:36',
                                'end_time' => '2015-01-11 23:53:37',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20150116183504' =>
                            [
                                'version' => '20150116183504',
                                'start_time' => '2015-01-16 18:35:40',
                                'end_time' => '2015-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20160111235330' =>
                            [
                                'version' => '20160111235330',
                                'start_time' => '2016-01-11 23:53:36',
                                'end_time' => '2016-01-11 23:53:37',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20160116183504' =>
                            [
                                'version' => '20160116183504',
                                'start_time' => '2016-01-16 18:35:40',
                                'end_time' => '2016-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                    ]
                ));

        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->getOutput()->setDecorated(false);
        $this->manager->setConfig($this->getConfigWithMixedNamespace());
        $return = $this->manager->printStatus('mockenv');
        $this->assertEquals(0, $return);

        rewind($this->manager->getOutput()->getStream());
        $outputStr = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertMatchesRegularExpression('/up  20120111235330  2012-01-11 23:53:36  2012-01-11 23:53:37  TestMigration/', $outputStr);
        $this->assertMatchesRegularExpression('/up  20120116183504  2012-01-16 18:35:40  2012-01-16 18:35:41  TestMigration2/', $outputStr);
        $this->assertMatchesRegularExpression('/up  20150111235330  2015-01-11 23:53:36  2015-01-11 23:53:37  Baz\\\\TestMigration/', $outputStr);
        $this->assertMatchesRegularExpression('/up  20150116183504  2015-01-16 18:35:40  2015-01-16 18:35:41  Baz\\\\TestMigration2/', $outputStr);
        $this->assertMatchesRegularExpression('/up  20160111235330  2016-01-11 23:53:36  2016-01-11 23:53:37  Foo\\\\Bar\\\\TestMigration/', $outputStr);
        $this->assertMatchesRegularExpression('/up  20160116183504  2016-01-16 18:35:40  2016-01-16 18:35:41  Foo\\\\Bar\\\\TestMigration2/', $outputStr);
    }

    public function testPrintStatusMethodWithNoMigrations()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();

        // override the migrations directory to an empty one
        $configArray = $this->getConfigArray();
        $configArray['paths']['migrations'] = $this->getCorrectedPath(__DIR__ . '/_files/nomigrations');
        $config = new Config($configArray);

        $this->manager->setConfig($config);
        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->getOutput()->setDecorated(false);
        $return = $this->manager->printStatus('mockenv');
        $this->assertEquals(0, $return);

        rewind($this->manager->getOutput()->getStream());
        $outputStr = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertMatchesRegularExpression('/There are no available migrations. Try creating one using the create command./', $outputStr);
    }

    public function testPrintStatusMethodWithMissingMigrations()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $envStub->expects($this->once())
                ->method('getVersionLog')
                ->will($this->returnValue(
                    [
                        '20120103083300' =>
                            [
                                'version' => '20120103083300',
                                'start_time' => '2012-01-11 23:53:36',
                                'end_time' => '2012-01-11 23:53:37',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20120815145812' =>
                            [
                                'version' => '20120815145812',
                                'start_time' => '2012-01-16 18:35:40',
                                'end_time' => '2012-01-16 18:35:41',
                                'migration_name' => 'Example',
                                'breakpoint' => '0',
                            ],
                    ]
                ));

        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->getOutput()->setDecorated(false);
        $return = $this->manager->printStatus('mockenv');
        $this->assertEquals(Manager::EXIT_STATUS_MISSING, $return);

        rewind($this->manager->getOutput()->getStream());
        $outputStr = stream_get_contents($this->manager->getOutput()->getStream());

        // note that the order is important: missing migrations should appear before down migrations
        $this->assertMatchesRegularExpression('/\s*up  20120103083300  2012-01-11 23:53:36  2012-01-11 23:53:37  *\*\* MISSING \*\*' . PHP_EOL .
            '\s*up  20120815145812  2012-01-16 18:35:40  2012-01-16 18:35:41  Example   *\*\* MISSING \*\*' . PHP_EOL .
            '\s*down  20120111235330                                            TestMigration' . PHP_EOL .
            '\s*down  20120116183504                                            TestMigration2/', $outputStr);
    }

    public function testPrintStatusMethodWithMissingMigrationsWithNamespace()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $envStub->expects($this->once())
                ->method('getVersionLog')
                ->will($this->returnValue(
                    [
                        '20160103083300' =>
                            [
                                'version' => '20160103083300',
                                'start_time' => '2016-01-11 23:53:36',
                                'end_time' => '2016-01-11 23:53:37',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20160815145812' =>
                            [
                                'version' => '20160815145812',
                                'start_time' => '2016-01-16 18:35:40',
                                'end_time' => '2016-01-16 18:35:41',
                                'migration_name' => 'Example',
                                'breakpoint' => '0',
                            ],
                    ]
                ));

        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->getOutput()->setDecorated(false);
        $this->manager->setConfig($this->getConfigWithNamespace());
        $return = $this->manager->printStatus('mockenv');
        $this->assertEquals(Manager::EXIT_STATUS_MISSING, $return);

        rewind($this->manager->getOutput()->getStream());
        $outputStr = stream_get_contents($this->manager->getOutput()->getStream());

        // note that the order is important: missing migrations should appear before down migrations
        $this->assertMatchesRegularExpression('/\s*up  20160103083300  2016-01-11 23:53:36  2016-01-11 23:53:37  *\*\* MISSING \*\*' . PHP_EOL .
            '\s*up  20160815145812  2016-01-16 18:35:40  2016-01-16 18:35:41  Example   *\*\* MISSING \*\*' . PHP_EOL .
            '\s*down  20160111235330                                            Foo\\\\Bar\\\\TestMigration' . PHP_EOL .
            '\s*down  20160116183504                                            Foo\\\\Bar\\\\TestMigration2/', $outputStr);
    }

    public function testPrintStatusMethodWithMissingMigrationsWithMixedNamespace()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $envStub->expects($this->once())
                ->method('getVersionLog')
                ->will($this->returnValue(
                    [
                        '20160103083300' =>
                            [
                                'version' => '20160103083300',
                                'start_time' => '2016-01-11 23:53:36',
                                'end_time' => '2016-01-11 23:53:37',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20160815145812' =>
                            [
                                'version' => '20160815145812',
                                'start_time' => '2016-01-16 18:35:40',
                                'end_time' => '2016-01-16 18:35:41',
                                'migration_name' => 'Example',
                                'breakpoint' => '0',
                            ],
                    ]
                ));

        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->getOutput()->setDecorated(false);
        $this->manager->setConfig($this->getConfigWithMixedNamespace());
        $return = $this->manager->printStatus('mockenv');
        $this->assertEquals(Manager::EXIT_STATUS_MISSING, $return);

        rewind($this->manager->getOutput()->getStream());
        $outputStr = stream_get_contents($this->manager->getOutput()->getStream());

        // note that the order is important: missing migrations should appear before down migrations
        $this->assertMatchesRegularExpression('/\s*up  20160103083300  2016-01-11 23:53:36  2016-01-11 23:53:37  *\*\* MISSING \*\*' . PHP_EOL .
            '\s*up  20160815145812  2016-01-16 18:35:40  2016-01-16 18:35:41  Example   *\*\* MISSING \*\*' . PHP_EOL .
            '\s*down  20120111235330                                            TestMigration' . PHP_EOL .
            '\s*down  20120116183504                                            TestMigration2' . PHP_EOL .
            '\s*down  20150111235330                                            Baz\\\\TestMigration' . PHP_EOL .
            '\s*down  20150116183504                                            Baz\\\\TestMigration2' . PHP_EOL .
            '\s*down  20160111235330                                            Foo\\\\Bar\\\\TestMigration' . PHP_EOL .
            '\s*down  20160116183504                                            Foo\\\\Bar\\\\TestMigration2/', $outputStr);
    }

    public function testPrintStatusMethodWithMissingLastMigration()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $envStub->expects($this->once())
                ->method('getVersionLog')
                ->will($this->returnValue(
                    [
                        '20120111235330' =>
                            [
                                'version' => '20120111235330',
                                'start_time' => '2012-01-16 18:35:40',
                                'end_time' => '2012-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => 0
                            ],
                        '20120116183504' =>
                            [
                                'version' => '20120116183504',
                                'start_time' => '2012-01-16 18:35:40',
                                'end_time' => '2012-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20120120145114' =>
                            [
                                'version' => '20120120145114',
                                'start_time' => '2012-01-20 14:51:14',
                                'end_time' => '2012-01-20 14:51:14',
                                'migration_name' => 'Example',
                                'breakpoint' => '0',
                            ],
                    ]
                ));

        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->getOutput()->setDecorated(false);
        $return = $this->manager->printStatus('mockenv');
        $this->assertEquals(Manager::EXIT_STATUS_MISSING, $return);

        rewind($this->manager->getOutput()->getStream());
        $outputStr = stream_get_contents($this->manager->getOutput()->getStream());

        // note that the order is important: missing migrations should appear before down migrations
        $this->assertMatchesRegularExpression('/\s*up  20120111235330  2012-01-16 18:35:40  2012-01-16 18:35:41  TestMigration' . PHP_EOL .
            '\s*up  20120116183504  2012-01-16 18:35:40  2012-01-16 18:35:41  TestMigration2' . PHP_EOL .
            '\s*up  20120120145114  2012-01-20 14:51:14  2012-01-20 14:51:14  Example   *\*\* MISSING \*\*/', $outputStr);
    }

    public function testPrintStatusMethodWithMissingLastMigrationWithNamespace()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $envStub->expects($this->once())
                ->method('getVersionLog')
                ->will($this->returnValue(
                    [
                        '20160111235330' =>
                            [
                                'version' => '20160111235330',
                                'start_time' => '2016-01-16 18:35:40',
                                'end_time' => '2016-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => 0
                            ],
                        '20160116183504' =>
                            [
                                'version' => '20160116183504',
                                'start_time' => '2016-01-16 18:35:40',
                                'end_time' => '2016-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20160120145114' =>
                            [
                                'version' => '20160120145114',
                                'start_time' => '2016-01-20 14:51:14',
                                'end_time' => '2016-01-20 14:51:14',
                                'migration_name' => 'Example',
                                'breakpoint' => '0',
                            ],
                    ]
                ));

        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->getOutput()->setDecorated(false);
        $this->manager->setConfig($this->getConfigWithNamespace());
        $return = $this->manager->printStatus('mockenv');
        $this->assertEquals(Manager::EXIT_STATUS_MISSING, $return);

        rewind($this->manager->getOutput()->getStream());
        $outputStr = stream_get_contents($this->manager->getOutput()->getStream());

        // note that the order is important: missing migrations should appear before down migrations
        $this->assertMatchesRegularExpression('/\s*up  20160111235330  2016-01-16 18:35:40  2016-01-16 18:35:41  Foo\\\\Bar\\\\TestMigration' . PHP_EOL .
            '\s*up  20160116183504  2016-01-16 18:35:40  2016-01-16 18:35:41  Foo\\\\Bar\\\\TestMigration2' . PHP_EOL .
            '\s*up  20160120145114  2016-01-20 14:51:14  2016-01-20 14:51:14  Example   *\*\* MISSING \*\*/', $outputStr);
    }

    public function testPrintStatusMethodWithMissingLastMigrationWithMixedNamespace()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $envStub->expects($this->once())
                ->method('getVersionLog')
                ->will($this->returnValue(
                    [
                        '20120111235330' =>
                            [
                                'version' => '20120111235330',
                                'start_time' => '2012-01-16 18:35:40',
                                'end_time' => '2012-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => 0
                            ],
                        '20120116183504' =>
                            [
                                'version' => '20120116183504',
                                'start_time' => '2012-01-16 18:35:40',
                                'end_time' => '2012-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20150111235330' =>
                            [
                                'version' => '20150111235330',
                                'start_time' => '2015-01-16 18:35:40',
                                'end_time' => '2015-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => 0
                            ],
                        '20150116183504' =>
                            [
                                'version' => '20150116183504',
                                'start_time' => '2015-01-16 18:35:40',
                                'end_time' => '2015-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20160111235330' =>
                            [
                                'version' => '20160111235330',
                                'start_time' => '2016-01-16 18:35:40',
                                'end_time' => '2016-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => 0
                            ],
                        '20160116183504' =>
                            [
                                'version' => '20160116183504',
                                'start_time' => '2016-01-16 18:35:40',
                                'end_time' => '2016-01-16 18:35:41',
                                'migration_name' => '',
                                'breakpoint' => '0',
                            ],
                        '20170120145114' =>
                            [
                                'version' => '20170120145114',
                                'start_time' => '2017-01-20 14:51:14',
                                'end_time' => '2017-01-20 14:51:14',
                                'migration_name' => 'Example',
                                'breakpoint' => '0',
                            ],
                    ]
                ));

        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->getOutput()->setDecorated(false);
        $this->manager->setConfig($this->getConfigWithMixedNamespace());
        $return = $this->manager->printStatus('mockenv');
        $this->assertEquals(Manager::EXIT_STATUS_MISSING, $return);

        rewind($this->manager->getOutput()->getStream());
        $outputStr = stream_get_contents($this->manager->getOutput()->getStream());

        // note that the order is important: missing migrations should appear before down migrations
        $this->assertMatchesRegularExpression(
            '/\s*up  20120111235330  2012-01-16 18:35:40  2012-01-16 18:35:41  TestMigration' . PHP_EOL .
            '\s*up  20120116183504  2012-01-16 18:35:40  2012-01-16 18:35:41  TestMigration2' . PHP_EOL .
            '\s*up  20150111235330  2015-01-16 18:35:40  2015-01-16 18:35:41  Baz\\\\TestMigration' . PHP_EOL .
            '\s*up  20150116183504  2015-01-16 18:35:40  2015-01-16 18:35:41  Baz\\\\TestMigration2' . PHP_EOL .
            '\s*up  20160111235330  2016-01-16 18:35:40  2016-01-16 18:35:41  Foo\\\\Bar\\\\TestMigration' . PHP_EOL .
            '\s*up  20160116183504  2016-01-16 18:35:40  2016-01-16 18:35:41  Foo\\\\Bar\\\\TestMigration2' . PHP_EOL .
            '\s*up  20170120145114  2017-01-20 14:51:14  2017-01-20 14:51:14  Example   *\*\* MISSING \*\*/',
            $outputStr
        );
    }

    /**
     * Test that ensures the status header is correctly printed with regards to the version order
     *
     * @dataProvider statusVersionOrderProvider
     *
     * @param array  $config
     * @param string $expectedStatusHeader
     */
    public function testPrintStatusMethodVersionOrderHeader($config, $expectedStatusHeader)
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $envStub->expects($this->once())
                ->method('getVersionLog')
                ->will($this->returnValue([]));

        $output = new RawBufferedOutput();
        $this->manager = new Manager($config, $this->input, $output);

        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $return = $this->manager->printStatus('mockenv');
        $this->assertEquals(Manager::EXIT_STATUS_DOWN, $return);

        $outputStr = $this->manager->getOutput()->fetch();
        $this->assertStringContainsString($expectedStatusHeader, $outputStr);
    }

    public function statusVersionOrderProvider()
    {
        // create the necessary configuration objects
        $configArray = $this->getConfigArray();

        $configWithNoVersionOrder = new Config($configArray);

        $configArray['version_order'] = Config::VERSION_ORDER_CREATION_TIME;
        $configWithCreationVersionOrder = new Config($configArray);

        $configArray['version_order'] = Config::VERSION_ORDER_EXECUTION_TIME;
        $configWithExecutionVersionOrder = new Config($configArray);

        return [
            'With the default version order' => [
                $configWithNoVersionOrder,
                ' Status  <info>[Migration ID]</info>  Started              Finished             Migration Name '
            ],
            'With the creation version order' => [
                $configWithCreationVersionOrder,
                ' Status  <info>[Migration ID]</info>  Started              Finished             Migration Name '
            ],
            'With the execution version order' => [
                $configWithExecutionVersionOrder,
                ' Status  Migration ID    <info>[Started          ]</info>  Finished             Migration Name '
            ]
        ];
    }

    public function testPrintStatusInvalidVersionOrderKO()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();

        $configArray = $this->getConfigArray();
        $configArray['version_order'] = 'invalid';
        $config = new Config($configArray);

        $this->manager = new Manager($config, $this->input, $this->output);

        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid version_order configuration option');
        $this->manager->printStatus('mockenv');
    }

    public function testGetMigrationsWithDuplicateMigrationVersions()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Duplicate migration - "' . $this->getCorrectedPath(__DIR__ . '/_files/duplicateversions/20120111235330_duplicate_migration_2.php') . '" has the same version as "20120111235330"'
        );
        $config = new Config(['paths' => ['migrations' => $this->getCorrectedPath(__DIR__ . '/_files/duplicateversions')]]);
        $manager = new Manager($config, $this->input, $this->output);
        $manager->getMigrations();
    }

    public function testGetMigrationsWithDuplicateMigrationVersionsWithNamespace()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Duplicate migration - "' . $this->getCorrectedPath(__DIR__ . '/_files_foo_bar/duplicateversions/20160111235330_duplicate_migration_2.php') . '" has the same version as "20160111235330"'
        );
        $config = new Config(['paths' => ['migrations' => ['Foo\Bar' => $this->getCorrectedPath(__DIR__ . '/_files_foo_bar/duplicateversions')]]]);
        $manager = new Manager($config, $this->input, $this->output);
        $manager->getMigrations();
    }

    public function testGetMigrationsWithDuplicateMigrationVersionsWithMixedNamespace()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Duplicate migration - "' . $this->getCorrectedPath(__DIR__ . '/_files_baz/duplicateversions_mix_ns/20120111235330_duplicate_migration_mixed_namespace_2.php') . '" has the same version as "20120111235330"'
        );
        $config = new Config(['paths' => [
            'migrations' => [
                $this->getCorrectedPath(__DIR__ . '/_files/duplicateversions_mix_ns'),
                'Baz' => $this->getCorrectedPath(__DIR__ . '/_files_baz/duplicateversions_mix_ns'),
            ]
        ]]);
        $manager = new Manager($config, $this->input, $this->output);
        $manager->getMigrations();
    }

    public function testGetMigrationsWithDuplicateMigrationNames()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Migration "20120111235331_duplicate_migration_name.php" has the same name as "20120111235330_duplicate_migration_name.php"'
        );
        $config = new Config(['paths' => ['migrations' => $this->getCorrectedPath(__DIR__ . '/_files/duplicatenames')]]);
        $manager = new Manager($config, $this->input, $this->output);
        $manager->getMigrations();
    }

    public function testGetMigrationsWithDuplicateMigrationNamesWithNamespace()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Migration "20160111235331_duplicate_migration_name.php" has the same name as "20160111235330_duplicate_migration_name.php"'
        );
        $config = new Config(['paths' => ['migrations' => ['Foo\Bar' => $this->getCorrectedPath(__DIR__ . '/_files_foo_bar/duplicatenames')]]]);
        $manager = new Manager($config, $this->input, $this->output);
        $manager->getMigrations();
    }

    public function testGetMigrationsWithInvalidMigrationClassName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Could not find class "InvalidClass" in file "' . $this->getCorrectedPath(__DIR__ . '/_files/invalidclassname/20120111235330_invalid_class.php') . '"'
        );
        $config = new Config(['paths' => ['migrations' => $this->getCorrectedPath(__DIR__ . '/_files/invalidclassname')]]);
        $manager = new Manager($config, $this->input, $this->output);
        $manager->getMigrations();
    }

    public function testGetMigrationsWithInvalidMigrationClassNameWithNamespace()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Could not find class "Foo\Bar\InvalidClass" in file "' . $this->getCorrectedPath(__DIR__ . '/_files_foo_bar/invalidclassname/20160111235330_invalid_class.php') . '"'
        );
        $config = new Config(['paths' => ['migrations' => ['Foo\Bar' => $this->getCorrectedPath(__DIR__ . '/_files_foo_bar/invalidclassname')]]]);
        $manager = new Manager($config, $this->input, $this->output);
        $manager->getMigrations();
    }

    public function testGetMigrationsWithClassThatDoesntExtendAbstractMigration()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'The class "InvalidSuperClass" in file "' . $this->getCorrectedPath(__DIR__ . '/_files/invalidsuperclass/20120111235330_invalid_super_class.php') . '" must extend \Phinx\Migration\AbstractMigration'
        );
        $config = new Config(['paths' => ['migrations' => $this->getCorrectedPath(__DIR__ . '/_files/invalidsuperclass')]]);
        $manager = new Manager($config, $this->input, $this->output);
        $manager->getMigrations();
    }

    public function testGetMigrationsWithClassThatDoesntExtendAbstractMigrationWithNamespace()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'The class "Foo\Bar\InvalidSuperClass" in file "' . $this->getCorrectedPath(__DIR__ . '/_files_foo_bar/invalidsuperclass/20160111235330_invalid_super_class.php') . '" must extend \Phinx\Migration\AbstractMigration'
        );
        $config = new Config(['paths' => ['migrations' => ['Foo\Bar' => $this->getCorrectedPath(__DIR__ . '/_files_foo_bar/invalidsuperclass')]]]);
        $manager = new Manager($config, $this->input, $this->output);
        $manager->getMigrations();
    }

    public function testGettingAValidEnvironment()
    {
        $this->assertInstanceOf(
            'Phinx\Migration\Manager\Environment',
            $this->manager->getEnvironment('production')
        );
    }

    /**
     * Test that migrating by date chooses the correct
     * migration to point to.
     *
     * @dataProvider migrateDateDataProvider
     *
     * @param array  $availableMigrations
     * @param string $dateString
     * @param string $expectedMigration
     * @param string $message
     */
    public function testMigrationsByDate(array $availableMigrations, $dateString, $expectedMigration, $message)
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        if (is_null($expectedMigration)) {
            $envStub->expects($this->never())
                    ->method('getVersions');
        } else {
            $envStub->expects($this->once())
                    ->method('getVersions')
                    ->will($this->returnValue($availableMigrations));
        }
        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->migrateToDateTime('mockenv', new \DateTime($dateString));
        rewind($this->manager->getOutput()->getStream());
        $output = stream_get_contents($this->manager->getOutput()->getStream());
        if (is_null($expectedMigration)) {
            $this->assertEmpty($output, $message);
        } else {
            $this->assertStringContainsString($expectedMigration, $output, $message);
        }
    }

    /**
     * Migration lists, dates, and expected migrations to point to.
     *
     * @return array
     */
    public function migrateDateDataProvider()
    {
        return [
            [['20120111235330', '20120116183504'], '20120118', '20120116183504', 'Failed to migrate all migrations when migrate to date is later than all the migrations'],
            [['20120111235330', '20120116183504'], '20120115', '20120111235330', 'Failed to migrate 1 migration when the migrate to date is between 2 migrations'],
            [['20120111235330', '20120116183504'], '20120111235330', '20120111235330', 'Failed to migrate 1 migration when the migrate to date is one of the migrations'],
            [['20120111235330', '20120116183504'], '20110115', null, 'Failed to migrate 0 migrations when the migrate to date is before all the migrations'],
        ];
    }

    public function testExecuteSeedWorksAsExpected()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->seed('mockenv');
        rewind($this->manager->getOutput()->getStream());
        $output = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertStringContainsString('GSeeder', $output);
        $this->assertStringContainsString('PostSeeder', $output);
        $this->assertStringContainsString('UserSeeder', $output);
    }

    public function testExecuteSeedWorksAsExpectedWithNamespace()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $this->manager->setConfig($this->getConfigWithNamespace());
        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->seed('mockenv');
        rewind($this->manager->getOutput()->getStream());
        $output = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertStringContainsString('Foo\Bar\GSeeder', $output);
        $this->assertStringContainsString('Foo\Bar\PostSeeder', $output);
        $this->assertStringContainsString('Foo\Bar\UserSeeder', $output);
    }

    public function testExecuteSeedWorksAsExpectedWithMixedNamespace()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $this->manager->setConfig($this->getConfigWithMixedNamespace());
        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->seed('mockenv');
        rewind($this->manager->getOutput()->getStream());
        $output = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertStringContainsString('GSeeder', $output);
        $this->assertStringContainsString('PostSeeder', $output);
        $this->assertStringContainsString('UserSeeder', $output);
        $this->assertStringContainsString('Baz\GSeeder', $output);
        $this->assertStringContainsString('Baz\PostSeeder', $output);
        $this->assertStringContainsString('Baz\UserSeeder', $output);
        $this->assertStringContainsString('Foo\Bar\GSeeder', $output);
        $this->assertStringContainsString('Foo\Bar\PostSeeder', $output);
        $this->assertStringContainsString('Foo\Bar\UserSeeder', $output);
    }

    public function testExecuteASingleSeedWorksAsExpected()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->seed('mockenv', 'UserSeeder');
        rewind($this->manager->getOutput()->getStream());
        $output = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertStringContainsString('UserSeeder', $output);
    }

    public function testExecuteASingleSeedWorksAsExpectedWithNamespace()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $this->manager->setConfig($this->getConfigWithNamespace());
        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->seed('mockenv', 'Foo\Bar\UserSeeder');
        rewind($this->manager->getOutput()->getStream());
        $output = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertStringContainsString('Foo\Bar\UserSeeder', $output);
    }

    public function testExecuteASingleSeedWorksAsExpectedWithMixedNamespace()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $this->manager->setConfig($this->getConfigWithMixedNamespace());
        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->manager->seed('mockenv', 'Baz\UserSeeder');
        rewind($this->manager->getOutput()->getStream());
        $output = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertStringContainsString('Baz\UserSeeder', $output);
    }

    public function testExecuteANonExistentSeedWorksAsExpected()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The seed class "NonExistentSeeder" does not exist');
        $this->manager->seed('mockenv', 'NonExistentSeeder');
        rewind($this->manager->getOutput()->getStream());
        $output = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertStringContainsString('UserSeeder', $output);
    }

    public function testExecuteANonExistentSeedWorksAsExpectedWithNamespace()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $this->manager->setConfig($this->getConfigWithNamespace());
        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The seed class "Foo\Bar\NonExistentSeeder" does not exist');
        $this->manager->seed('mockenv', 'Foo\Bar\NonExistentSeeder');
        rewind($this->manager->getOutput()->getStream());
        $output = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertStringContainsString('Foo\Bar\UserSeeder', $output);
    }

    public function testExecuteANonExistentSeedWorksAsExpectedWithMixedNamespace()
    {
        // stub environment
        $envStub = $this->getMockBuilder('\Phinx\Migration\Manager\Environment')
            ->setConstructorArgs(['mockenv', []])
            ->getMock();
        $this->manager->setConfig($this->getConfigWithMixedNamespace());
        $this->manager->setEnvironments(['mockenv' => $envStub]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The seed class "Baz\NonExistentSeeder" does not exist');
        $this->manager->seed('mockenv', 'Baz\NonExistentSeeder');
        rewind($this->manager->getOutput()->getStream());
        $output = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertStringContainsString('UserSeeder', $output);
        $this->assertStringContainsString('Baz\UserSeeder', $output);
        $this->assertStringContainsString('Foo\Bar\UserSeeder', $output);
    }

    public function testOrderSeeds()
    {
        $seeds = array_values($this->manager->getSeeds());
        $this->assertInstanceOf('UserSeeder', $seeds[0]);
        $this->assertInstanceOf('GSeeder', $seeds[1]);
        $this->assertInstanceOf('PostSeeder', $seeds[2]);
    }

    public function testGettingInputObject()
    {
        $migrations = $this->manager->getMigrations();
        $seeds = $this->manager->getSeeds();
        $inputObject = $this->manager->getInput();
        $this->assertInstanceOf('\Symfony\Component\Console\Input\InputInterface', $inputObject);

        foreach ($migrations as $migration) {
            $this->assertEquals($inputObject, $migration->getInput());
        }
        foreach ($seeds as $seed) {
            $this->assertEquals($inputObject, $seed->getInput());
        }
    }

    public function testGettingOutputObject()
    {
        $migrations = $this->manager->getMigrations();
        $seeds = $this->manager->getSeeds();
        $outputObject = $this->manager->getOutput();
        $this->assertInstanceOf('\Symfony\Component\Console\Output\OutputInterface', $outputObject);

        foreach ($migrations as $migration) {
            $this->assertEquals($outputObject, $migration->getOutput());
        }
        foreach ($seeds as $seed) {
            $this->assertEquals($outputObject, $seed->getOutput());
        }
    }

    public function testGettingAnInvalidEnvironment()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The environment "invalidenv" does not exist');
        $this->manager->getEnvironment('invalidenv');
    }

    public function setExpectedException($exceptionName, $exceptionMessage = '', $exceptionCode = null)
    {
        //PHPUnit 5+
        $this->expectException($exceptionName);
        if ($exceptionMessage !== '') {
            $this->expectExceptionMessage($exceptionMessage);
        }
        if ($exceptionCode !== null) {
            $this->expectExceptionCode($exceptionCode);
        }
    }
}

/**
 * RawBufferedOutput is a specialized BufferedOutput that outputs raw "writeln" calls (ie. it doesn't replace the
 * tags like <info>message</info>.
 */
class RawBufferedOutput extends \Symfony\Component\Console\Output\BufferedOutput
{
    public function writeln($messages, $options = self::OUTPUT_RAW)
    {
        $this->write($messages, true, $options);
    }
}
