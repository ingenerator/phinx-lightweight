<?php

namespace Test\Phinx\Util;

use Phinx\Util\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    private function getCorrectedPath($path)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    public function testGetExistingMigrationClassNames()
    {
        $expectedResults = [
            'TestMigration',
            'TestMigration2',
        ];

        $existingClassNames = Util::getExistingMigrationClassNames($this->getCorrectedPath(__DIR__ . '/_files/migrations'));
        $this->assertCount(count($expectedResults), $existingClassNames);
        foreach ($expectedResults as $expectedResult) {
            $this->assertContains($expectedResult, $existingClassNames);
        }
    }

    public function testGetExistingMigrationClassNamesWithFile()
    {
        $file = $this->getCorrectedPath(__DIR__ . '/_files/migrations/20120111235330_test_migration.php');
        $existingClassNames = Util::getExistingMigrationClassNames($file);
        $this->assertCount(0, $existingClassNames);
    }

    public function testGetCurrentTimestamp()
    {
        $dt = new \DateTime('now', new \DateTimeZone('UTC'));
        $expected = $dt->format(Util::DATE_FORMAT);

        $current = Util::getCurrentTimestamp();

        // Rather than using a strict equals, we use greater/lessthan checks to
        // prevent false positives when the test hits the edge of a second.
        $this->assertGreaterThanOrEqual($expected, $current);
        // We limit the assertion time to 2 seconds, which should never fail.
        $this->assertLessThanOrEqual($expected + 2, $current);
    }

    public function testMapClassNameToFileName()
    {
        $expectedResults = [
            'CamelCase87afterSomeBooze' => '/^\d{14}_camel_case87after_some_booze\.php$/',
            'CreateUserTable' => '/^\d{14}_create_user_table\.php$/',
            'LimitResourceNamesTo30Chars' => '/^\d{14}_limit_resource_names_to30_chars\.php$/',
        ];

        foreach ($expectedResults as $input => $expectedResult) {
            $this->assertMatchesRegularExpression($expectedResult, Util::mapClassNameToFileName($input));
        }
    }

    public function testMapFileNameToClassName()
    {
        $expectedResults = [
            '20150902094024_create_user_table.php' => 'CreateUserTable',
            '20150902102548_my_first_migration2.php' => 'MyFirstMigration2',
        ];

        foreach ($expectedResults as $input => $expectedResult) {
            $this->assertEquals($expectedResult, Util::mapFileNameToClassName($input));
        }
    }

    public function testisValidPhinxClassName()
    {
        $expectedResults = [
            'CAmelCase' => false,
            'CreateUserTable' => true,
            'Test' => true,
            'test' => false
        ];

        foreach ($expectedResults as $input => $expectedResult) {
            $this->assertEquals($expectedResult, Util::isValidPhinxClassName($input));
        }
    }

    public function testGlobPath()
    {
        $files = Util::glob(__DIR__ . '/_files/migrations/empty.txt');
        $this->assertCount(1, $files);
        $this->assertEquals('empty.txt', basename($files[0]));

        $files = Util::glob(__DIR__ . '/_files/migrations/*.php');
        $this->assertCount(3, $files);
        $this->assertEquals('20120111235330_test_migration.php', basename($files[0]));
        $this->assertEquals('20120116183504_test_migration_2.php', basename($files[1]));
        $this->assertEquals('not_a_migration.php', basename($files[2]));
    }

    public function testGlobAll()
    {
        $files = Util::globAll([
            __DIR__ . '/_files/migrations/*.php',
            __DIR__ . '/_files/migrations/subdirectory/*.txt'
        ]);

        $this->assertCount(4, $files);
        $this->assertEquals('20120111235330_test_migration.php', basename($files[0]));
        $this->assertEquals('20120116183504_test_migration_2.php', basename($files[1]));
        $this->assertEquals('not_a_migration.php', basename($files[2]));
        $this->assertEquals('empty.txt', basename($files[3]));
    }

    /**
     * Returns array of dsn string and expected parsed array.
     *
     * @return array
     */
    public function providerDsnStrings()
    {
        return [
            [
                'mysql://user:pass@host:1234/name?charset=utf8&other_param=value!',
                [
                    'charset' => 'utf8',
                    'other_param' => 'value!',
                    'adapter' => 'mysql',
                    'user' => 'user',
                    'pass' => 'pass',
                    'host' => 'host',
                    'port' => '1234',
                    'name' => 'name',
                ],
            ],
            [
                'pgsql://user:pass@host/name?',
                [
                    'adapter' => 'pgsql',
                    'user' => 'user',
                    'pass' => 'pass',
                    'host' => 'host',
                    'name' => 'name',
                ],
            ],
            [
                'sqlsrv://host:1234/name',
                [
                    'adapter' => 'sqlsrv',
                    'host' => 'host',
                    'port' => '1234',
                    'name' => 'name',
                ],
            ],
            [
                'sqlite://user:pass@host/name',
                [
                    'adapter' => 'sqlite',
                    'user' => 'user',
                    'pass' => 'pass',
                    'host' => 'host',
                    'name' => 'name',
                ],
            ],
            [
                'pgsql://host/name',
                [
                    'adapter' => 'pgsql',
                    'host' => 'host',
                    'name' => 'name',
                ],
            ],
            [
                'pdomock://user:pass!@host/name',
                [
                    'adapter' => 'pdomock',
                    'user' => 'user',
                    'pass' => 'pass!',
                    'host' => 'host',
                    'name' => 'name',
                ],
            ],
            [
                'pdomock://user:pass@host/:1234/name',
                [
                    'adapter' => 'pdomock',
                    'user' => 'user',
                    'pass' => 'pass',
                    'host' => 'host',
                    'name' => ':1234/name',
                ],
            ],
            [
                'pdomock://user:pa:ss@host:1234/name',
                [
                    'adapter' => 'pdomock',
                    'user' => 'user',
                    'pass' => 'pa:ss',
                    'host' => 'host',
                    'port' => '1234',
                    'name' => 'name',
                ],
            ],
            [
                'pdomock://:pass@host:1234/name',
                [
                    'adapter' => 'pdomock',
                    'pass' => 'pass',
                    'host' => 'host',
                    'port' => '1234',
                    'name' => 'name',
                ],
            ],
            [
                'sqlite:///:memory:',
                [
                    'adapter' => 'sqlite',
                    'name' => ':memory:',
                ],
            ],
            ['pdomock://user:pass@host:/name', []],
            ['pdomock://user:pass@:1234/name', []],
            ['://user:pass@host:1234/name', []],
            ['pdomock:/user:p@ss@host:1234/name', []],
        ];
    }

    /**
     * Tests parsing dsn strings.
     *
     * @dataProvider providerDsnStrings
     * @return void
     */
    public function testParseDsn($dsn, $expected)
    {
        $this->assertSame($expected, Util::parseDsn($dsn));
    }
}
