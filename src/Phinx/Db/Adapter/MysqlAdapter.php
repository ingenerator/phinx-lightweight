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

use Phinx\Db\Table\Column;
use Phinx\Db\Table\ForeignKey;
use Phinx\Db\Table\Index;

/**
 * Phinx MySQL Adapter.
 *
 * @author Rob Morgan <robbym@gmail.com>
 */
class MysqlAdapter extends PdoAdapter implements AdapterInterface
{

    protected $signedColumnTypes = ['integer' => true, 'biginteger' => true, 'float' => true, 'decimal' => true, 'boolean' => true];

    const TEXT_TINY = 255;
    const TEXT_SMALL = 255; /* deprecated, alias of TEXT_TINY */
    const TEXT_REGULAR = 65535;
    const TEXT_MEDIUM = 16777215;
    const TEXT_LONG = 4294967295;

    // According to https://dev.mysql.com/doc/refman/5.0/en/blob.html BLOB sizes are the same as TEXT
    const BLOB_TINY = 255;
    const BLOB_SMALL = 255; /* deprecated, alias of BLOB_TINY */
    const BLOB_REGULAR = 65535;
    const BLOB_MEDIUM = 16777215;
    const BLOB_LONG = 4294967295;

    const INT_TINY = 255;
    const INT_SMALL = 65535;
    const INT_MEDIUM = 16777215;
    const INT_REGULAR = 4294967295;
    const INT_BIG = 18446744073709551615;

    const TYPE_YEAR = 'year';

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if ($this->connection === null) {
            if (!class_exists('PDO') || !in_array('mysql', \PDO::getAvailableDrivers(), true)) {
                // @codeCoverageIgnoreStart
                throw new \RuntimeException('You need to enable the PDO_Mysql extension for Phinx to run properly.');
                // @codeCoverageIgnoreEnd
            }

            $db = null;
            $options = $this->getOptions();

            $dsn = 'mysql:';

            if (!empty($options['unix_socket'])) {
                // use socket connection
                $dsn .= 'unix_socket=' . $options['unix_socket'];
            } else {
                // use network connection
                $dsn .= 'host=' . $options['host'];
                if (!empty($options['port'])) {
                    $dsn .= ';port=' . $options['port'];
                }
            }

            $dsn .= ';dbname=' . $options['name'];

            // charset support
            if (!empty($options['charset'])) {
                $dsn .= ';charset=' . $options['charset'];
            }

            $driverOptions = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];

            // support arbitrary \PDO::MYSQL_ATTR_* driver options and pass them to PDO
            // http://php.net/manual/en/ref.pdo-mysql.php#pdo-mysql.constants
            foreach ($options as $key => $option) {
                if (strpos($key, 'mysql_attr_') === 0) {
                    $driverOptions[constant('\PDO::' . strtoupper($key))] = $option;
                }
            }

            try {
                $db = new \PDO($dsn, $options['user'], $options['pass'], $driverOptions);
            } catch (\PDOException $exception) {
                throw new \InvalidArgumentException(sprintf(
                    'There was a problem connecting to the database: %s',
                    $exception->getMessage()
                ));
            }

            $this->setConnection($db);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->connection = null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTransactions()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->execute('START TRANSACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function commitTransaction()
    {
        $this->execute('COMMIT');
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackTransaction()
    {
        $this->execute('ROLLBACK');
    }

    /**
     * {@inheritdoc}
     */
    public function quoteTableName($tableName)
    {
        return str_replace('.', '`.`', $this->quoteColumnName($tableName));
    }

    /**
     * {@inheritdoc}
     */
    public function quoteColumnName($columnName)
    {
        return '`' . str_replace('`', '``', $columnName) . '`';
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable($tableName)
    {
        $options = $this->getOptions();

        $exists = $this->fetchRow(sprintf(
            "SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s'",
            $options['name'],
            $tableName
        ));

        return !empty($exists);
    }

    protected function ensureSchemaTableExistsAndCorrect(): void
    {
        $tableName       = $this->getSchemaTableName();
        $quotedTableName = $this->quoteTableName($tableName);

        if ( ! $this->hasTable($tableName)) {
            // Create the schema table if it doesn't already exist
            $this->execute(
                <<<SQL
                CREATE TABLE $quotedTableName (
                    `version` bigint(20) NOT NULL,
                    `migration_name` varchar(100) DEFAULT NULL,
                    `start_time` timestamp NULL DEFAULT NULL,
                    `end_time` timestamp NULL DEFAULT NULL,
                    `breakpoint` tinyint(1) NOT NULL DEFAULT '0',
                    PRIMARY KEY (`version`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
                SQL
            );

            return;
        }

        // The schema table exists but may need upgrading
        $columnNames = array_map(
            fn($col) => \strtolower($col['Field']),
            $this->fetchAll(sprintf('SHOW COLUMNS FROM %s', $quotedTableName))
        );

        if ( ! \in_array('migration_name', $columnNames)) {
            $this->execute(
                <<<SQL
                ALTER TABLE $quotedTableName
                    ADD COLUMN `migration_name` varchar(100) DEFAULT NULL AFTER `version`;
            SQL
            );
        }

        if ( ! \in_array('breakpoint', $columnNames)) {
            $this->execute(
                <<<SQL
                ALTER TABLE $quotedTableName
                    ADD COLUMN `breakpoint` tinyint(1) NOT NULL DEFAULT '0' AFTER `end_time`;
            SQL
            );
        }
    }

}
