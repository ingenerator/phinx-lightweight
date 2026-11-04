<?php

namespace Test\Phinx\Config;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use UnexpectedValueException;
use Phinx\Config\Config;

/**
 * Class ConfigMigrationPathsTest
 * @package Test\Phinx\Config
 */
#[CoversMethod(\Phinx\Config\Config::class, 'getMigrationPaths')]
#[Group('config')]
class ConfigMigrationPathsTest extends AbstractConfigTest
{

    public function testGetMigrationPathsThrowsExceptionForNoPath()
    {
        $config = new Config([]);
        $this->expectException(UnexpectedValueException::class);
        $config->getMigrationPaths();
    }

    /**
     * Normal behavior
     */
    public function testGetMigrationPaths()
    {
        $config = new Config($this->getConfigArray());
        $this->assertEquals($this->getMigrationPaths(), $config->getMigrationPaths());
    }

    public function testGetMigrationPathConvertsStringToArray()
    {
        $values = [
            'paths' => [
                'migrations' => '/test'
            ]
        ];

        $config = new Config($values);
        $paths = $config->getMigrationPaths();

        $this->assertIsArray($paths);
        $this->assertCount(1, $paths);
    }
}
