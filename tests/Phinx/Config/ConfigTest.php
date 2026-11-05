<?php

namespace Test\Phinx\Config;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Phinx\Config\Config;

/**
 * Class ConfigTest
 * @package Test\Phinx\Config
 */
#[CoversMethod(Config::class, '__construct')]
#[CoversMethod(Config::class, 'getConfigFilePath')]
#[CoversMethod(Config::class, 'getEnvironments')]
#[CoversMethod(Config::class, 'hasEnvironment')]
#[CoversMethod(Config::class, 'getEnvironment')]
#[CoversMethod(Config::class, 'offsetGet')]
#[CoversMethod(Config::class, 'offsetSet')]
#[CoversMethod(Config::class, 'offsetExists')]
#[CoversMethod(Config::class, 'offsetUnset')]
#[CoversMethod(Config::class, 'getMigrationBaseClassName')]
#[CoversMethod(Config::class, 'getTemplateFile')]
#[CoversMethod(Config::class, 'getTemplateClass')]
#[CoversMethod(Config::class, 'getVersionOrder')]
#[CoversMethod(Config::class, 'isVersionOrderCreationTime')]
#[Group('config')]
class ConfigTest extends AbstractConfigTestCase
{
    public function testConstructEmptyArguments()
    {
        $config = new Config([]);
        // this option is set to its default value when not being passed in the constructor, so we can ignore it
        unset($config['version_order']);
        $this->assertNull($config->getConfigFilePath());
    }

    public function testConstructByArray()
    {
        $config = new Config($this->getConfigArray());
        $this->assertNull($config->getConfigFilePath());
    }

    public function testGetEnvironmentsMethod()
    {
        $config = new Config($this->getConfigArray());
        $this->assertCount(2, $config->getEnvironments());
        $this->assertArrayHasKey('testing', $config->getEnvironments());
        $this->assertArrayHasKey('production', $config->getEnvironments());
    }

    public function testHasEnvironmentDoesntHave()
    {
        $config = new Config([]);
        $this->assertFalse($config->hasEnvironment('dummy'));
    }

    public function testHasEnvironmentHasOne()
    {
        $config = new Config($this->getConfigArray());
        $this->assertTrue($config->hasEnvironment('testing'));
    }

    public function testGetEnvironmentsNotSet()
    {
        $config = new Config([]);
        $this->assertNull($config->getEnvironments());
    }

    public function testGetEnvironmentMethod()
    {
        $config = new Config($this->getConfigArray());
        $db = $config->getEnvironment('testing');
        $this->assertEquals('mysql', $db['adapter']);
    }

    public function testHasEnvironmentMethod()
    {
        $configArray = $this->getConfigArray();
        $config = new Config($configArray);
        $this->assertTrue($config->hasEnvironment('testing'));
        $this->assertFalse($config->hasEnvironment('fakeenvironment'));
    }

    public function testArrayAccessMethods()
    {
        $config = new Config([]);
        $config['foo'] = 'bar';
        $this->assertEquals('bar', $config['foo']);
        $this->assertArrayHasKey('foo', $config);
        unset($config['foo']);
        $this->assertArrayNotHasKey('foo', $config);
    }

    public function testUndefinedArrayAccess()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Identifier "foo" is not defined.');
        $config = new Config([]);
        $config['foo'];
    }

    public function testGetMigrationBaseClassNameGetsDefaultBaseClass()
    {
        $config = new Config([]);
        $this->assertEquals('AbstractMigration', $config->getMigrationBaseClassName());
    }

    public function testGetMigrationBaseClassNameGetsDefaultBaseClassWithNamespace()
    {
        $config = new Config([]);
        $this->assertEquals('Phinx\Migration\AbstractMigration', $config->getMigrationBaseClassName(false));
    }

    public function testGetMigrationBaseClassNameGetsAlternativeBaseClass()
    {
        $config = new Config(['migration_base_class' => 'Phinx\Migration\AlternativeAbstractMigration']);
        $this->assertEquals('AlternativeAbstractMigration', $config->getMigrationBaseClassName());
    }

    public function testGetMigrationBaseClassNameGetsAlternativeBaseClassWithNamespace()
    {
        $config = new Config(['migration_base_class' => 'Phinx\Migration\AlternativeAbstractMigration']);
        $this->assertEquals('Phinx\Migration\AlternativeAbstractMigration', $config->getMigrationBaseClassName(false));
    }

    public function testGetTemplateValuesFalseOnEmpty()
    {
        $config = new Config([]);
        $this->assertFalse($config->getTemplateFile());
        $this->assertFalse($config->getTemplateClass());
    }

    public function testGetAliasNoAliasesEntry()
    {
        $config = new Config([]);
        $this->assertNull($config->getAlias('Short'));
    }

    public function testGetAliasEmptyAliasesEntry()
    {
        $config = new Config(['aliases' => []]);
        $this->assertNull($config->getAlias('Short'));
    }

    public function testGetAliasInvalidAliasRequest()
    {
        $config = new Config(['aliases' => ['Medium' => 'Some\Long\Classname']]);
        $this->assertNull($config->getAlias('Short'));
    }

    public function testGetAliasValidAliasRequest()
    {
        $config = new Config(['aliases' => ['Short' => 'Some\Long\Classname']]);
        $this->assertEquals('Some\Long\Classname', $config->getAlias('Short'));
    }

    /**
     * Checks if base class is returned correctly when specified without
     * a namespace.
     */
    public function testGetMigrationBaseClassNameNoNamespace()
    {
        $config = new Config(['migration_base_class' => 'BaseMigration']);
        $this->assertEquals('BaseMigration', $config->getMigrationBaseClassName());
    }

    /**
     * Checks if base class is returned correctly when specified without
     * a namespace.
     */
    public function testGetMigrationBaseClassNameNoNamespaceNoDrop()
    {
        $config = new Config(['migration_base_class' => 'BaseMigration']);
        $this->assertEquals('BaseMigration', $config->getMigrationBaseClassName(false));
    }

    public function testGetVersionOrder()
    {
        $config = new Config([]);
        $config['version_order'] = Config::VERSION_ORDER_EXECUTION_TIME;
        $this->assertEquals(Config::VERSION_ORDER_EXECUTION_TIME, $config->getVersionOrder());
    }

    #[DataProvider('isVersionOrderCreationTimeDataProvider')]
    public function testIsVersionOrderCreationTime($versionOrder, $expected)
    {
        // get config stub
        $configStub = $this->getMockBuilder('\Phinx\Config\Config')
            ->setConstructorArgs([[]])
            ->onlyMethods(['getVersionOrder'])
            ->getMock();

        $configStub->expects($this->once())
            ->method('getVersionOrder')
            ->willReturn($versionOrder);

        $this->assertEquals($expected, $configStub->isVersionOrderCreationTime());
    }

    public static function isVersionOrderCreationTimeDataProvider()
    {
        return [
            'With Creation Time Version Order' =>
            [
                Config::VERSION_ORDER_CREATION_TIME, true
            ],
            'With Execution Time Version Order' =>
            [
                Config::VERSION_ORDER_EXECUTION_TIME, false
            ],
        ];
    }
}
