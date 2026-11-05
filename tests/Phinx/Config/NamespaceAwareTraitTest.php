<?php

namespace Test\Phinx\Config;

use Phinx\Config\NamespaceAwareTrait;
use PHPUnit\Framework\TestCase;

class NamespaceAwareTraitTest extends TestCase
{
    public function testGetMigrationNamespaceByPath()
    {
        $config = new class {
            use NamespaceAwareTrait;

            public array $paths = [];

            public function getMigrationPaths()
            {
                return $this->paths;
            }
        };
        $config->paths = [
            __DIR__ . '/_files',
            'Baz' => __DIR__ . '/_rootDirectories/all/../OnlyPhp',
            'Foo\Bar' => __DIR__ . '/_rootDirectories/all',
        ];
        /** @var NamespaceAwareTrait $config */
        $this->assertNull($config->getMigrationNamespaceByPath(__DIR__ . '/_files'));
        $this->assertNull($config->getMigrationNamespaceByPath(__DIR__ . '/_rootDirectories/../_files'));
        $this->assertEquals('Baz', $config->getMigrationNamespaceByPath(__DIR__ . '/_rootDirectories/OnlyPhp'));
        $this->assertEquals('Foo\Bar', $config->getMigrationNamespaceByPath(__DIR__ . '/_rootDirectories/all'));
        $this->assertEquals('Foo\Bar', $config->getMigrationNamespaceByPath(__DIR__ . '/_rootDirectories/OnlyPhp/../all'));
    }

}
