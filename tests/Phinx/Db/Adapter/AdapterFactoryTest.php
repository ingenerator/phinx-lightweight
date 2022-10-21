<?php

namespace Test\Phinx\Db\Adapter;

use Phinx\Db\Adapter\AdapterFactory;
use PHPUnit\Framework\TestCase;

class AdapterFactoryTest extends TestCase
{
    /**
     * @var \Phinx\Db\Adapter\AdapterFactory
     */
    private $factory;

    public function setUp(): void
    {
        $this->factory = AdapterFactory::instance();
    }

    public function tearDown(): void
    {
        unset($this->factory);
    }

    public function testInstanceIsFactory()
    {
        $this->assertInstanceOf('Phinx\Db\Adapter\AdapterFactory', $this->factory);
    }

    public function testRegisterAdapter()
    {
        // AdapterFactory::getClass is protected, work around it to avoid
        // creating unnecessary instances and making the test more complex.
        $method = new \ReflectionMethod(get_class($this->factory), 'getClass');
        $method->setAccessible(true);

        $adapter = $method->invoke($this->factory, 'mysql');
        $this->factory->registerAdapter('test', $adapter);

        $this->assertEquals($adapter, $method->invoke($this->factory, 'test'));
    }

    public function testRegisterAdapterFailure()
    {
        $adapter = get_class($this);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Adapter class "Test\Phinx\Db\Adapter\AdapterFactoryTest" must implement Phinx\Db\Adapter\AdapterInterface');
        $this->factory->registerAdapter('test', $adapter);
    }

    public function testGetAdapter()
    {
        $adapter = $this->factory->getAdapter('mysql', []);

        $this->assertInstanceOf('Phinx\Db\Adapter\MysqlAdapter', $adapter);
    }

    public function testGetAdapterFailure()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Adapter "bad" has not been registered');
        $this->factory->getAdapter('bad', []);
    }

}
