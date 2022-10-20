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
 * @subpackage Phinx\Migration
 */

namespace Phinx\Migration;

use Phinx\Db\Adapter\AdapterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract Migration Class.
 *
 * It is expected that the migrations you write extend from this class.
 *
 * This abstract class proxies the various database methods to your specified
 * adapter.
 *
 * @author Rob Morgan <robbym@gmail.com>
 */
abstract class AbstractMigration implements MigrationInterface
{
    /**
     * @var float
     */
    protected $version;

    /**
     * @var \Phinx\Db\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * Class Constructor.
     *
     * @param int                                                    $version Migration Version
     * @param \Symfony\Component\Console\Input\InputInterface|null   $input
     * @param \Symfony\Component\Console\Output\OutputInterface|null $output
     */
    final public function __construct($version, InputInterface $input = NULL, OutputInterface $output = NULL)
    {
        $this->version = $version;
        if ( ! is_null($input)) {
            $this->setInput($input);
        }
        if ( ! is_null($output)) {
            $this->setOutput($output);
        }

        $this->init();
    }

    final public function change(): void
    {
        // Prevent migration classes from defining or calling a `change()` method as we no longer support this.
        // This will provide a runtime catch for any legacy migration classes that are doing the wrong thing.
        throw new \BadMethodCallException(
            'Unexpected call to '.__METHOD__.' - migration classes should only use `up()`'
        );
    }

    final public function down(): void
    {
        // Prevent migration classes from defining or calling a `down()` method as we no longer support this.
        // This will provide a runtime catch for any legacy migration classes that are doing the wrong thing.
        throw new \BadMethodCallException(
            'Unexpected call to '.__METHOD__.' - migration classes should only use `up()`'
        );
    }

    /**
     * Initialize method.
     *
     * @return void
     */
    protected function init()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
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
    public function setInput(InputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * {@inheritdoc}
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return get_class($this);
    }

    /**
     * {@inheritdoc}
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * {@inheritdoc}
     */
    public function isMigratingUp()
    {
        return TRUE;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(string $sql): false|int
    {
        return $this->getAdapter()->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $sql): \PDOStatement
    {
        return $this->getAdapter()->query($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchRow(string $sql): array|false
    {
        return $this->getAdapter()->fetchRow($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll(string $sql): array
    {
        return $this->getAdapter()->fetchAll($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(string $table, array $data): void
    {
        if (isset($data[0]) && \is_array($data[0])) {
            // We have been given multiple rows
            $rows = $data;
        } else {
            // We have been given a single row
            $rows = [$data];
        }

        $expect_keys = array_keys($rows[0]);
        foreach ($rows as $row) {
            if (array_keys($row) !== $expect_keys) {
                throw new \InvalidArgumentException(
                    <<<TEXT
                    AbstractMigration::insert() no longer supports inserts with mixed column keys

                    ->insert() used to automagically guess whether to perform a bulk insert or single inserts
                    depending on whether each row item had the same columns. This can produce very inefficient
                    inserts. If you need to insert different column values in different rows, your code should
                    work out how to logically split & batch these into insert statements and call ->insert()
                    separately for each batch.
                    TEXT
                );
            }
        }

        $this->getAdapter()->bulkinsert($table, $rows);
    }

    public function prepareAndExecute(string $sql, array $params): void
    {
        $this->getAdapter()->getConnection()->prepare($sql)->execute($params);
    }

}
