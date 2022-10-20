.. index::
   single: Writing Migrations

Writing Migrations
==================

Phinx relies on migrations in order to transform your database. Each migration
is represented by a PHP class in a unique file. It is preferred that you write
your migrations using the Phinx PHP API, but raw SQL is also supported.

Creating a New Migration
------------------------
Generating a skeleton migration file
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Let's start by creating a new Phinx migration. Run Phinx using the ``create``
command:

.. code-block:: bash

        $ php vendor/bin/phinx create MyNewMigration

This will create a new migration in the format
``YYYYMMDDHHMMSS_my_new_migration.php``, where the first 14 characters are
replaced with the current timestamp down to the second.

If you have specified multiple migration paths, you will be asked to select
which path to create the new migration in.

Phinx automatically creates a skeleton migration file with a single method:

.. code-block:: php

        <?php

        use Phinx\Migration\AbstractMigration;

        class MyNewMigration extends AbstractMigration
        {
            /**
             * Make your database changes in this method.
             */
            public function up()
            {

            }
        }

All Phinx migrations extend from the ``AbstractMigration`` class. This class
provides the necessary support to create your database migrations. Database
migrations can transform your database in many ways, such as creating new
tables, inserting rows, adding indexes and modifying columns.

The Up Method
~~~~~~~~~~~~~

The up method is automatically run by Phinx when you are migrating up and it
detects the given migration hasn't been executed previously. You should use the
up method to transform the database with your intended changes.

Executing Queries
-----------------

Queries can be executed with the ``execute()`` and ``query()`` methods. The
``execute()`` method returns the number of affected rows whereas the
``query()`` method returns the result as a
`PDOStatement <http://php.net/manual/en/class.pdostatement.php>`_

.. code-block:: php

        <?php

        use Phinx\Migration\AbstractMigration;

        class MyNewMigration extends AbstractMigration
        {
            /**
             * Migrate Up.
             */
            public function up()
            {
                // execute()
                $count = $this->execute('DELETE FROM users'); // returns the number of affected rows

                // query()
                $stmt = $this->query('SELECT * FROM users'); // returns PDOStatement
                $rows = $stmt->fetchAll(); // returns the result as an array
            }

        }

.. note::

    These commands run using the PHP Data Objects (PDO) extension which
    defines a lightweight, consistent interface for accessing databases
    in PHP. Always make sure your queries abide with PDOs before using
    the ``execute()`` command. This is especially important when using
    DELIMITERs during insertion of stored procedures or triggers which
    don't support DELIMITERs.

.. warning::

    When using ``execute()`` or ``query()`` with a batch of queries, PDO doesn't
    throw an exception if there is an issue with one or more of the queries
    in the batch.

    As such, the entire batch is assumed to have passed without issue.

    If Phinx was to iterate any potential result sets, looking to see if one
    had an error, then Phinx would be denying access to all the results as there
    is no facility in PDO to get a previous result set
    `nextRowset() <http://php.net/manual/en/pdostatement.nextrowset.php>`_ -
    but no ``previousSet()``).

    So, as a consequence, due to the design decision in PDO to not throw
    an exception for batched queries, Phinx is unable to provide the fullest
    support for error handling when batches of queries are supplied.

    Fortunately though, all the features of PDO are available, so multiple batches
    can be controlled within the migration by calling upon
    `nextRowset() <http://php.net/manual/en/pdostatement.nextrowset.php>`_
    and examining `errorInfo <http://php.net/manual/en/pdostatement.errorinfo.php>`_.

Fetching Rows
-------------

There are two methods available to fetch rows. The ``fetchRow()`` method will
fetch a single row, whilst the ``fetchAll()`` method will return multiple rows.
Both methods accept raw SQL as their only parameter.

.. code-block:: php

        <?php

        use Phinx\Migration\AbstractMigration;

        class MyNewMigration extends AbstractMigration
        {
            /**
             * Migrate Up.
             */
            public function up()
            {
                // fetch a user
                $row = $this->fetchRow('SELECT * FROM users');

                // fetch an array of messages
                $rows = $this->fetchAll('SELECT * FROM messages');
            }

        }

Inserting Data
--------------

Phinx makes it easy to insert data into your tables.
.. code-block:: php

        <?php

        use Phinx\Migration\AbstractMigration;

        class NewStatus extends AbstractMigration
        {
            /**
             * Migrate Up.
             */
            public function up()
            {
                // inserting only one row
                $singleRow = [
                    'id'    => 1,
                    'name'  => 'In Progress'
                ];

                $table = $this->table('status');
                $table->insert($singleRow);
                $table->saveData();

                // inserting multiple rows
                $rows = [
                    [
                      'id'    => 2,
                      'name'  => 'Stopped'
                    ],
                    [
                      'id'    => 3,
                      'name'  => 'Queued'
                    ]
                ];

                // this is a handy shortcut
                $this->insert('status', $rows);
            }

        }

