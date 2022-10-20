<?php
/**
 * Phinx
 *
 * (The MIT license)
 * Copyright (c) 2015 Rob Morgan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated * documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package    Phinx
 * @subpackage Phinx\Db\Adapter
 */
namespace Phinx\Db\Adapter;

use Phinx\Db\Table;
use Phinx\Db\Table\Column;
use Phinx\Db\Table\ForeignKey;
use Phinx\Db\Table\Index;
use Phinx\Migration\MigrationInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adapter Wrapper.
 *
 * Proxy commands through to another adapter, allowing modification of
 * parameters during calls.
 *
 * @author Woody Gilk <woody.gilk@gmail.com>
 */
abstract class AdapterWrapper implements AdapterInterface, WrapperInterface
{
    /**
     * @var \Phinx\Db\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     * {@inheritdoc}
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->setAdapter($adapter);
    }

    /**
     * {@inheritdoc}
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->adapter->setOptions($options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->adapter->getOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function hasOption($name)
    {
        return $this->adapter->hasOption($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($name)
    {
        return $this->adapter->getOption($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setInput(InputInterface $input)
    {
        $this->adapter->setInput($input);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getInput()
    {
        return $this->adapter->getInput();
    }

    /**
     * {@inheritdoc}
     */
    public function setOutput(OutputInterface $output)
    {
        $this->adapter->setOutput($output);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOutput()
    {
        return $this->adapter->getOutput();
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        $this->getAdapter()->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->getAdapter()->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    public function execute($sql)
    {
        return $this->getAdapter()->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function query($sql)
    {
        return $this->getAdapter()->query($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(string $table_name, array $row): void
    {
        $this->getAdapter()->insert($table_name, $row);
    }

    /**
     * {@inheritdoc}
     */
    public function bulkinsert(string $table_name, array $rows): void
    {
        $this->getAdapter()->bulkinsert($table_name, $rows);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchRow($sql)
    {
        return $this->getAdapter()->fetchRow($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($sql)
    {
        return $this->getAdapter()->fetchAll($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersions()
    {
        return $this->getAdapter()->getVersions();
    }

    /**
     * {@inheritdoc}
     */
    public function getVersionLog()
    {
        return $this->getAdapter()->getVersionLog();
    }

    /**
     * {@inheritdoc}
     */
    public function migrated(MigrationInterface $migration, $startTime, $endTime)
    {
        $this->getAdapter()->migrated($migration, $startTime, $endTime);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnTypes()
    {
        return $this->getAdapter()->getColumnTypes();
    }

    /**
     * {@inheritdoc}
     */
    public function hasTransactions()
    {
        return $this->getAdapter()->hasTransactions();
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->getAdapter()->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commitTransaction()
    {
        $this->getAdapter()->commitTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackTransaction()
    {
        $this->getAdapter()->rollbackTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function quoteTableName($tableName)
    {
        return $this->getAdapter()->quoteTableName($tableName);
    }

    /**
     * {@inheritdoc}
     */
    public function quoteColumnName($columnName)
    {
        return $this->getAdapter()->quoteColumnName($columnName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable($tableName)
    {
        return $this->getAdapter()->hasTable($tableName);
    }

    /**
     * {@inheritdoc}
     */
    public function castToBool($value)
    {
        return $this->getAdapter()->castToBool($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->getAdapter()->getConnection();
    }
}
