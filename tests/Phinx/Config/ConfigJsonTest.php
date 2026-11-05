<?php

namespace Test\Phinx\Config;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Phinx\Config\Config;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigJsonTest
 * @package Test\Phinx\Config
 */
#[CoversMethod(Config::class, 'fromJson')]
#[Group('config')]
class ConfigJsonTest extends TestCase
{
    public function testFromJSONMethod()
    {
        $path = __DIR__ . '/_files';
        $config = Config::fromJson($path . '/valid_config.json');
        $this->assertEquals('dev', $config->getDefaultEnvironment());
    }

    public function testFromJSONInvalidJson()
    {
        $path = __DIR__ . '/_files';
        $this->expectException(RuntimeException::class);
        Config::fromJson($path . '/invalid.json');
    }
}
