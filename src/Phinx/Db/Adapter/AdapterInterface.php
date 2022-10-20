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
 * Adapter Interface.
 *
 * @author Rob Morgan <robbym@gmail.com>
 * @method \PDO getConnection()
 */
interface AdapterInterface
{
    const PHINX_TYPE_STRING = 'string';
    const PHINX_TYPE_CHAR = 'char';
    const PHINX_TYPE_TEXT = 'text';
    const PHINX_TYPE_INTEGER = 'integer';
    const PHINX_TYPE_BIG_INTEGER = 'biginteger';
    const PHINX_TYPE_FLOAT = 'float';
    const PHINX_TYPE_DECIMAL = 'decimal';
    const PHINX_TYPE_DATETIME = 'datetime';
    const PHINX_TYPE_TIMESTAMP = 'timestamp';
    const PHINX_TYPE_TIME = 'time';
    const PHINX_TYPE_DATE = 'date';
    const PHINX_TYPE_BINARY = 'binary';
    const PHINX_TYPE_VARBINARY = 'varbinary';
    const PHINX_TYPE_BLOB = 'blob';
    const PHINX_TYPE_BOOLEAN = 'boolean';
    const PHINX_TYPE_JSON = 'json';
    const PHINX_TYPE_JSONB = 'jsonb';
    const PHINX_TYPE_UUID = 'uuid';
    const PHINX_TYPE_FILESTREAM = 'filestream';

    // Geospatial database types
    const PHINX_TYPE_GEOMETRY = 'geometry';
    const PHINX_TYPE_POINT = 'point';
    const PHINX_TYPE_LINESTRING = 'linestring';
    const PHINX_TYPE_POLYGON = 'polygon';

    // only for mysql so far
    const PHINX_TYPE_ENUM = 'enum';
    const PHINX_TYPE_SET = 'set';

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
     * @param  array $options
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
     * @param  string $name
     * @return bool
     */
    public function hasOption($name);

    /**
     * Get a single adapter option, or null if the option does not exist.
     *
     * @param  string $name
     * @return mixed
     */
    public function getOption($name);

    /**
     * Sets the console input.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input Input
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
     * Toggle a migration breakpoint.
     *
     * @param \Phinx\Migration\MigrationInterface $migration
     *
     * @return \Phinx\Db\Adapter\AdapterInterface
     */
    public function toggleBreakpoint(MigrationInterface $migration);

    /**
     * Reset all migration breakpoints.
     *
     * @return int The number of breakpoints reset
     */
    public function resetAllBreakpoints();

    /**
     * Does the schema table exist?
     *
     * @deprecated use hasTable instead.
     * @return bool
     */
    public function hasSchemaTable();

    /**
     * Creates the schema table.
     *
     * @return void
     */
    public function createSchemaTable();

    /**
     * Returns the adapter type.
     *
     * @return string
     */
    public function getAdapterType();

    /**
     * Initializes the database connection.
     *
     * @throws \RuntimeException When the requested database driver is not installed.
     * @return void
     */
    public function connect();

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
     * @return int
     */
    public function execute($sql);

    /**
     * Executes a SQL statement and returns the result as an array.
     *
     * @param string $sql SQL
     * @return mixed
     */
    public function query($sql);

    /**
     * Executes a query and returns only one row as an array.
     *
     * @param string $sql SQL
     * @return array
     */
    public function fetchRow($sql);

    /**
     * Executes a query and returns an array of rows.
     *
     * @param string $sql SQL
     * @return array
     */
    public function fetchAll($sql);

    /**
     * Inserts data into a table.
     *
     * @param \Phinx\Db\Table $table where to insert data
     * @param array $row
     * @return void
     */
    public function insert(Table $table, $row);

    /**
     * Inserts data into a table in a bulk.
     *
     * @param \Phinx\Db\Table $table where to insert data
     * @param array $rows
     * @return void
     */
    public function bulkinsert(Table $table, $rows);

    /**
     * Quotes a table name for use in a query.
     *
     * @param string $tableName Table Name
     * @return string
     */
    public function quoteTableName($tableName);

    /**
     * Quotes a column name for use in a query.
     *
     * @param string $columnName Table Name
     * @return string
     */
    public function quoteColumnName($columnName);

    /**
     * Checks to see if a table exists.
     *
     * @param string $tableName Table Name
     * @return bool
     */
    public function hasTable($tableName);

    /**
     * Checks to see if a column exists.
     *
     * @param string $tableName  Table Name
     * @param string $columnName Column Name
     * @return bool
     */
    public function hasColumn($tableName, $columnName);

    /**
     * Returns an array of the supported Phinx column types.
     *
     * @return array
     */
    public function getColumnTypes();

    /**
     * Cast a value to a boolean appropriate for the adapter.
     *
     * @param mixed $value The value to be cast
     *
     * @return mixed
     */
    public function castToBool($value);
}
