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

use PDOStatement;
use Phinx\Migration\MigrationInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adapter Interface.
 *
 * @author Rob Morgan <robbym@gmail.com>
 */
interface AdapterInterface
{

    /**
     * Get all migrated version numbers.
     *
     * @return array
     */
    public function getVersions();

    /**
     * Get all migration log entries, indexed by version creation time and sorted ascendingly by the configuration's
     * version order option
     *
     * @return array
     */
    public function getVersionLog();

    /**
     * Set adapter configuration options.
     *
     * @param array $options
     *
     * @return \Phinx\Db\Adapter\AdapterInterface
     */
    public function setOptions(array $options);

    /**
     * Get all adapter options.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Check if an option has been set.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasOption($name);

    /**
     * Get a single adapter option, or null if the option does not exist.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getOption($name);

    /**
     * Sets the console input.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input Input
     *
     * @return \Phinx\Db\Adapter\AdapterInterface
     */
    public function setInput(InputInterface $input);

    /**
     * Gets the console input.
     *
     * @return \Symfony\Component\Console\Input\InputInterface
     */
    public function getInput();

    /**
     * Sets the console output.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output Output
     *
     * @return \Phinx\Db\Adapter\AdapterInterface
     */
    public function setOutput(OutputInterface $output);

    /**
     * Gets the console output.
     *
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getOutput();

    /**
     * Records a migration being run.
     *
     * @param \Phinx\Migration\MigrationInterface $migration Migration
     * @param int                                 $startTime Start Time
     * @param int                                 $endTime   End Time
     *
     * @return \Phinx\Db\Adapter\AdapterInterface
     */
    public function migrated(MigrationInterface $migration, $startTime, $endTime);

    /**
     * Returns the adapter type.
     *
     * @return string
     */
    public function getAdapterType();

    /**
     * Initializes the database connection.
     *
     * @return void
     * @throws \RuntimeException When the requested database driver is not installed.
     */
    public function connect();

    /**
     * Get the current database connection
     *
     * @return \PDO
     */
    public function getConnection(): \PDO;

    /**
     * Closes the database connection.
     *
     * @return void
     */
    public function disconnect();

    /**
     * Does the adapter support transactions?
     *
     * @return bool
     */
    public function hasTransactions();

    /**
     * Begin a transaction.
     *
     * @return void
     */
    public function beginTransaction();

    /**
     * Commit a transaction.
     *
     * @return void
     */
    public function commitTransaction();

    /**
     * Rollback a transaction.
     *
     * @return void
     */
    public function rollbackTransaction();

    /**
     * Executes a SQL statement and returns the number of affected rows.
     *
     * @param string $sql SQL
     *
     * @return false|int
     */
    public function execute(string $sql): false|int;

    /**
     * Executes a SQL statement and returns the result as a PDO statement.
     *
     * @param string $sql SQL
     *
     * @return PDOStatement
     */
    public function query(string $sql): PDOStatement;

    /**
     * Executes a query and returns only one row as an associative array.
     *
     * @param string $sql SQL
     *
     * @return array|false
     */
    public function fetchRow(string $sql): array|false;

    /**
     * Executes a query and returns an array of rows.
     *
     * @param string $sql SQL
     *
     * @return array
     */
    public function fetchAll(string $sql): array;

    /**
     * Inserts data into a table.
     *
     * @param string $table_name where to insert data
     * @param array  $row
     *
     * @return void
     */
    public function insert(string $table_name, array $row): void;

    /**
     * Inserts data into a table in a bulk.
     *
     * @param string $table where to insert data
     * @param array  $rows
     *
     * @return void
     */
    public function bulkinsert(string $table_name, array $rows): void;

    /**
     * Quotes a table name for use in a query.
     *
     * @param string $tableName Table Name
     *
     * @return string
     */
    public function quoteTableName($tableName);

    /**
     * Quotes a column name for use in a query.
     *
     * @param string $columnName Table Name
     *
     * @return string
     */
    public function quoteColumnName($columnName);

}
