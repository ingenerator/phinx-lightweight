<?php

namespace Test\Phinx\Migration\Manager;

use PDO;
use RuntimeException;
use stdClass;
use PHPUnit\Framework\Attributes\TestWith;
use InvalidArgumentException;
use Phinx\Db\Adapter\AdapterFactory;
use Phinx\Db\Adapter\PdoAdapter;
use Phinx\Migration\AbstractMigration;
use Phinx\Migration\Manager\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTestDummyAdapter extends PdoAdapter
{
    public function getAdapterType() { return 'pdomock'; }
    public function hasTransactions() { return true; }
    public function beginTransaction() {}
    public function commitTransaction() {}
    public function rollbackTransaction() {}
    public function quoteTableName($tableName) { return $tableName; }
    public function quoteColumnName($columnName) { return $columnName; }
    protected function ensureSchemaTableExistsAndCorrect(): void {}
}

class PDOMock extends PDO
{
    public $attributes = [];

    public function __construct()
    {
    }

    public function getAttribute(int $attribute): mixed
    {
        return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : 'pdomock';
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        $this->attributes[$attribute] = $value;

        return TRUE;
    }
}

class EnvironmentTest extends TestCase
{
    /**
     * @var Environment
     */
    private $environment;

    public function setUp(): void
    {
        $this->environment = new Environment('test', []);
    }

    public function testConstructorWorksAsExpected()
    {
        $env = new Environment('testenv', ['foo' => 'bar']);
        $this->assertEquals('testenv', $env->getName());
        $this->assertArrayHasKey('foo', $env->getOptions());
    }

    public function testSettingTheName()
    {
        $this->environment->setName('prod123');
        $this->assertEquals('prod123', $this->environment->getName());
    }

    public function testSettingOptions()
    {
        $this->environment->setOptions(['foo' => 'bar']);
        $this->assertArrayHasKey('foo', $this->environment->getOptions());
    }

    public function testInvalidAdapter()
    {
        $this->environment->setOptions(['adapter' => 'fakeadapter']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Adapter "fakeadapter" has not been registered');
        $this->environment->getAdapter();
    }

    public function testNoAdapter()
    {
        $this->expectException(RuntimeException::class);
        $this->environment->getAdapter();
    }

    public function testGetAdapterWithExistingPdoInstance()
    {
        AdapterFactory::instance()->registerAdapter('pdomock', EnvironmentTestDummyAdapter::class);
        $this->environment->setOptions(['connection' => new PDOMock()]);
        $options = $this->environment->getAdapter()->getOptions();
        $this->assertEquals('pdomock', $options['adapter']);
    }

    public function testSetPdoAttributeToErrmodeException()
    {
        $pdoMock = new PDOMock();
        AdapterFactory::instance()->registerAdapter('pdomock', EnvironmentTestDummyAdapter::class);
        $this->environment->setOptions(['connection' => $pdoMock]);
        $options = $this->environment->getAdapter()->getOptions();
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $options['connection']->getAttribute(PDO::ATTR_ERRMODE));
    }

    public function testGetAdapterWithBadExistingPdoInstance()
    {
        $this->environment->setOptions(['connection' => new stdClass()]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('The specified connection is not a PDO instance');
        $this->environment->getAdapter();
    }

    #[TestWith([['table_prefix' => 'tbl_']])]
    #[TestWith([['table_suffix' => '_dev']])]
    public function testTablePrefixAdapter($opts)
    {
        $this->environment->setOptions(array_merge(['adapter' => 'mysql'], $opts));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Automatic table prefix/suffix option is no longer supported');
        $this->environment->getAdapter();
    }

    public function testSchemaName()
    {
        $this->assertEquals('phinxlog', $this->environment->getSchemaTableName());

        $this->environment->setSchemaTableName('changelog');
        $this->assertEquals('changelog', $this->environment->getSchemaTableName());
    }

    public function testCurrentVersion()
    {
        $stub = $this->createStub(PdoAdapter::class);
        $stub
             ->method('getVersions')
             ->willReturn(['20110301080000']);

        $this->environment->setAdapter($stub);

        $this->assertEquals('20110301080000', $this->environment->getCurrentVersion());
    }

    public function testExecutingAMigrationUp()
    {
        // stub adapter
        $adapterStub = $this->createMock(PdoAdapter::class);
        $adapterStub->expects($this->once())
                    ->method('migrated')
                    ->willReturnArgument(0);

        $this->environment->setAdapter($adapterStub);

        // up
        $upMigration = $this->createMock(AbstractMigration::class);
        $upMigration->expects($this->once())
                    ->method('up');

        $this->environment->executeMigration($upMigration);
    }

    public function testExecutingAMigrationWithTransactions()
    {
        // stub adapter
        $adapterStub = $this->createMock(PdoAdapter::class);
        $adapterStub->expects($this->once())
                    ->method('beginTransaction');

        $adapterStub->expects($this->once())
                    ->method('commitTransaction');

        $adapterStub->expects($this->exactly(2))
                    ->method('hasTransactions')
                    ->willReturn(true);

        $this->environment->setAdapter($adapterStub);

        // migrate
        $migration = $this->createMock(AbstractMigration::class);
        $migration->expects($this->once())
                  ->method('up');

        $this->environment->executeMigration($migration);
    }

    public function testGettingInputObject()
    {
        $mock = $this->createStub('\Symfony\Component\Console\Input\InputInterface');
        $this->environment->setInput($mock);
        $inputObject = $this->environment->getInput();
        $this->assertInstanceOf('\Symfony\Component\Console\Input\InputInterface', $inputObject);
    }
}
