<?php

namespace Test\Phinx\Config;

use PHPUnit\Framework\TestCase;

/**
 * Class AbstractConfigTest
 * @package Test\Phinx\Config
 * @group config
 * @coversNothing
 */
abstract class AbstractConfigTest extends TestCase
{
    /**
     * @var string
     */
    protected $migrationPath = null;

    /**
     * Returns a sample configuration array for use with the unit tests.
     *
     * @return array
     */
    public function getConfigArray()
    {
        return [
            'default' => [
                'paths' => [
                    'migrations' => '%%PHINX_CONFIG_PATH%%/testmigrations2',
                ]
            ],
            'paths' => [
                'migrations' => $this->getMigrationPaths(),
            ],
            'templates' => [
                'file' => '%%PHINX_CONFIG_PATH%%/tpl/testtemplate.txt',
                'class' => '%%PHINX_CONFIG_PATH%%/tpl/testtemplate.php'
            ],
            'environments' => [
                'default_migration_table' => 'phinxlog',
                'default_database' => 'testing',
                'testing' => [
                    'adapter' => 'mysql',
                ],
                'production' => [
                    'adapter' => 'mysql'
                ]
            ]
        ];
    }

    /**
     * Generate dummy migration paths
     *
     * @return string[]
     */
    protected function getMigrationPaths()
    {
        if (null === $this->migrationPath) {
            $this->migrationPath = uniqid('phinx', true);
        }

        return [$this->migrationPath];
    }

}
