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

/**
 * Table prefix/suffix adapter.
 *
 * Used for inserting a prefix or suffix into table names.
 *
 * @author Samuel Fisher <sam@sfisher.co>
 */
class TablePrefixAdapter extends AdapterWrapper
{
    /**
     * {@inheritdoc}
     */
    public function getAdapterType()
    {
        return 'TablePrefixAdapter';
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable($tableName)
    {
        $adapterTableName = $this->getAdapterTableName($tableName);

        return parent::hasTable($adapterTableName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasColumn($tableName, $columnName)
    {
        $adapterTableName = $this->getAdapterTableName($tableName);

        return parent::hasColumn($adapterTableName, $columnName);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(Table $table, $row)
    {
        $adapterTable = clone $table;
        $adapterTableName = $this->getAdapterTableName($table->getName());
        $adapterTable->setName($adapterTableName);
        parent::insert($adapterTable, $row);
    }

    /**
     * {@inheritdoc}
     */
    public function bulkinsert(Table $table, $rows)
    {
        $adapterTable = clone $table;
        $adapterTableName = $this->getAdapterTableName($table->getName());
        $adapterTable->setName($adapterTableName);
        parent::bulkinsert($adapterTable, $rows);
    }

    /**
     * Gets the table prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return (string)$this->getOption('table_prefix');
    }

    /**
     * Gets the table suffix.
     *
     * @return string
     */
    public function getSuffix()
    {
        return (string)$this->getOption('table_suffix');
    }

    /**
     * Applies the prefix and suffix to the table name.
     *
     * @param string $tableName
     * @return string
     */
    public function getAdapterTableName($tableName)
    {
        return $this->getPrefix() . $tableName . $this->getSuffix();
    }
}
