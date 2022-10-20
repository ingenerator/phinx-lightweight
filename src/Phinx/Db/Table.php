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
 * @subpackage Phinx\Db
 */
namespace Phinx\Db;

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Table\Column;
use Phinx\Db\Table\ForeignKey;
use Phinx\Db\Table\Index;

/**
 *
 * This object is based loosely on: http://api.rubyonrails.org/classes/ActiveRecord/ConnectionAdapters/Table.html.
 */
class Table
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var \Phinx\Db\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Class Constuctor.
     *
     * @param string $name Table Name
     * @param array $options Options
     * @param \Phinx\Db\Adapter\AdapterInterface $adapter Database Adapter
     */
    public function __construct($name, $options = [], AdapterInterface $adapter = null)
    {
        $this->setName($name);

        if ($adapter !== null) {
            $this->setAdapter($adapter);
        }
    }

    /**
     * Sets the table name.
     *
     * @param string $name Table Name
     * @return \Phinx\Db\Table
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the table name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the table options.
     * @deprecated
     * @return array
     */
    public function getOptions()
    {
        return [];
    }

    /**
     * Sets the database adapter.
     *
     * @param \Phinx\Db\Adapter\AdapterInterface $adapter Database Adapter
     * @return \Phinx\Db\Table
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
        return $this->adapter;
    }

    /**
     * Sets an array of data to be inserted.
     *
     * @param array $data Data
     * @return \Phinx\Db\Table
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Gets the data waiting to be inserted.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Resets all of the pending table changes.
     *
     * @return void
     */
    public function reset()
    {
        $this->setData([]);
    }


    /**
     * Insert data into the table.
     *
     * @param array $data array of data in the form:
     *              array(
     *                  array("col1" => "value1", "col2" => "anotherValue1"),
     *                  array("col2" => "value2", "col2" => "anotherValue2"),
     *              )
     *              or array("col1" => "value1", "col2" => "anotherValue1")
     *
     * @return \Phinx\Db\Table
     */
    public function insert($data)
    {
        // handle array of array situations
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $row) {
                $this->data[] = $row;
            }

            return $this;
        }
        $this->data[] = $data;

        return $this;
    }

    /**
     * Commit the pending data waiting for insertion.
     *
     * @return void
     */
    public function saveData()
    {
        $rows = $this->getData();
        if (empty($rows)) {
            return;
        }

        $bulk = true;
        $row = current($rows);
        $c = array_keys($row);
        foreach ($this->getData() as $row) {
            $k = array_keys($row);
            if ($k != $c) {
                $bulk = false;
                break;
            }
        }

        if ($bulk) {
            $this->getAdapter()->bulkinsert($this, $this->getData());
        } else {
            foreach ($this->getData() as $row) {
                $this->getAdapter()->insert($this, $row);
            }
        }
    }

}
