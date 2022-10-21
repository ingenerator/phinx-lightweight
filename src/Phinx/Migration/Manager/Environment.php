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
 * @subpackage Phinx\Migration\Manager
 */
namespace Phinx\Migration\Manager;

use Phinx\Db\Adapter\AdapterFactory;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Migration\MigrationInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Environment
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var int
     */
    protected $currentVersion;

    /**
     * @var string
     */
    protected $schemaTableName = 'phinxlog';

    /**
     * @var \Phinx\Db\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     * Class Constructor.
     *
     * @param string $name Environment Name
     * @param array $options Options
     * @return \Phinx\Migration\Manager\Environment
     */
    public function __construct($name, $options)
    {
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * Executes the specified migration on this environment.
     *
     * @param \Phinx\Migration\MigrationInterface $migration Migration
     *
     * @return void
     */
    public function executeMigration(MigrationInterface $migration)
    {
        $startTime = time();
        $migration->setAdapter($this->getAdapter());

        // begin the transaction if the adapter supports it
        if ($this->getAdapter()->hasTransactions()) {
            $this->getAdapter()->beginTransaction();
        }

        $migration->up();

        // commit the transaction if the adapter supports it
        if ($this->getAdapter()->hasTransactions()) {
            $this->getAdapter()->commitTransaction();
        }

        // Record it in the database
        $this->getAdapter()->migrated($migration, date('Y-m-d H:i:s', $startTime), date('Y-m-d H:i:s', time()));
    }

    /**
     * Sets the environment's name.
     *
     * @param string $name Environment Name
     * @return \Phinx\Migration\Manager\Environment
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the environment name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the environment's options.
     *
     * @param array $options Environment Options
     * @return \Phinx\Migration\Manager\Environment
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Gets the environment's options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Sets the console input.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return \Phinx\Migration\Manager\Environment
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Gets the console input.
     *
     * @return \Symfony\Component\Console\Input\InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Sets the console output.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output Output
     * @return \Phinx\Migration\Manager\Environment
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Gets the console output.
     *
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Gets all migrated version numbers.
     *
     * @return array
     */
    public function getVersions()
    {
        return $this->getAdapter()->getVersions();
    }

    /**
     * Get all migration log entries, indexed by version creation time and sorted ascendingly by the configuration's
     * version_order option
     *
     * @return array
     */
    public function getVersionLog()
    {
        return $this->getAdapter()->getVersionLog();
    }

    /**
     * Sets the current version of the environment.
     *
     * @param int $version Environment Version
     * @return \Phinx\Migration\Manager\Environment
     */
    public function setCurrentVersion($version)
    {
        $this->currentVersion = $version;

        return $this;
    }

    /**
     * Gets the current version of the environment.
     *
     * @return int
     */
    public function getCurrentVersion()
    {
        // We don't cache this code as the current version is pretty volatile.
        // TODO - that means they're no point in a setter then?
        // maybe we should cache and call a reset() method everytime a migration is run
        $versions = $this->getVersions();
        $version = 0;

        if (!empty($versions)) {
            $version = end($versions);
        }

        $this->setCurrentVersion($version);

        return $this->currentVersion;
    }

    /**
     * Sets the database adapter.
     *
     * @param \Phinx\Db\Adapter\AdapterInterface $adapter Database Adapter
     * @return \Phinx\Migration\Manager\Environment
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * Gets the database adapter.
     *
     * @return \Phinx\Db\Adapter\AdapterInterface
     */
    public function getAdapter()
    {
        if (isset($this->adapter)) {
            return $this->adapter;
        }
        if (isset($this->options['connection'])) {
            if (!($this->options['connection'] instanceof \PDO)) {
                throw new \RuntimeException('The specified connection is not a PDO instance');
            }

            $this->options['connection']->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->options['adapter'] = $this->options['connection']->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }
        if (!isset($this->options['adapter'])) {
            throw new \RuntimeException('No adapter was specified for environment: ' . $this->getName());
        }

        $factory = AdapterFactory::instance();
        $adapter = $factory
            ->getAdapter($this->options['adapter'], $this->options);

        if (isset($this->options['wrapper'])) {
            throw new \InvalidArgumentException('The `wrapper` option has been removed');
        }

        if ($this->getInput()) {
            $adapter->setInput($this->getInput());
        }

        if ($this->getOutput()) {
            $adapter->setOutput($this->getOutput());
        }

        // Use the TablePrefixAdapter if table prefix/suffixes are in use
        if ($adapter->hasOption('table_prefix') || $adapter->hasOption('table_suffix')) {
            throw new \InvalidArgumentException(
                <<<TEXT
                Automatic table prefix/suffix option is no longer supported.

                We advise against automagically modifying table names (this never worked with raw sql statements
                anyway). Specify the explicit table names you need in your migration files. If you absolutely
                need to apply a variation based on environment, you should implement this yourself e.g. by setting
                a process environment variable and reading it in your migration files as required.
                TEXT
            );
        }

        $this->setAdapter($adapter);

        return $adapter;
    }

    /**
     * Sets the schema table name.
     *
     * @param string $schemaTableName Schema Table Name
     * @return \Phinx\Migration\Manager\Environment
     */
    public function setSchemaTableName($schemaTableName)
    {
        $this->schemaTableName = $schemaTableName;

        return $this;
    }

    /**
     * Gets the schema table name.
     *
     * @return string
     */
    public function getSchemaTableName()
    {
        return $this->schemaTableName;
    }
}
