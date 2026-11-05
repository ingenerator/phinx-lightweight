<?php

namespace Test\Phinx\Config;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Phinx\Config\Config;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigPhpTest
 * @package Test\Phinx\Config
 */
#[CoversMethod(Config::class, 'fromPhp')]
#[CoversMethod(Config::class, 'getDefaultEnvironment')]
#[Group('config')]
class ConfigPhpTest extends TestCase
{
    public function testFromPHPMethod()
    {
        $path = __DIR__ . '/_files';
        $config = Config::fromPhp($path . '/valid_config.php');
        $this->assertEquals('dev', $config->getDefaultEnvironment());
    }

    public function testFromPHPMethodWithoutArray()
    {
        $path = __DIR__ . '/_files';
        $this->expectException(RuntimeException::class);
        $config = Config::fromPhp($path . '/config_without_array.php');
        $this->assertEquals('dev', $config->getDefaultEnvironment());
    }

    public function testFromJSONMethodWithoutJSON()
    {
        $path = __DIR__ . '/_files';
        $this->expectException(RuntimeException::class);
        $config = Config::fromPhp($path . '/empty.json');
        $this->assertEquals('dev', $config->getDefaultEnvironment());
    }
}
