.. index::
   single: Commands

Commands
========

Phinx is run using a number of commands.

The Create Command
------------------

The Create command is used to create a new migration file. It requires one
argument: the name of the migration. The migration name should be specified in
CamelCase format.

.. code-block:: bash

        $ phinx create MyNewMigration

Open the new migration file in your text editor to add your database
transformations. Phinx creates migration files using the path specified in your
``phinx.yml`` file. Please see the :doc:`Configuration <configuration>` chapter
for more information.

You are able to override the template file used by Phinx by supplying an
alternative template filename.

.. code-block:: bash

        $ phinx create MyNewMigration --template="<file>"

You can also supply a template generating class. This class must implement the
interface ``Phinx\Migration\CreationInterface``.

.. code-block:: bash

        $ phinx create MyNewMigration --class="<class>"

In addition to providing the template for the migration, the class can also define
a callback that will be called once the migration file has been generated from the
template.

You cannot use ``--template`` and ``--class`` together.

The Init Command
----------------

The Init command (short for initialize) is used to prepare your project for
Phinx. This command generates the ``phinx.yml`` file in the root of your
project directory.

.. code-block:: bash

        $ cd yourapp
        $ phinx init .

Open this file in your text editor to setup your project configuration. Please
see the :doc:`Configuration <configuration>` chapter for more information.

The Migrate Command
-------------------

The Migrate command runs all of the available migrations, optionally up to a
specific version.

.. code-block:: bash

        $ phinx migrate -e development

To migrate to a specific version then use the ``--target`` parameter or ``-t``
for short.

.. code-block:: bash

        $ phinx migrate -e development -t 20110103081132

The Status Command
------------------

The Status command prints a list of all migrations, along with their current
status. You can use this command to determine which migrations have been run.

.. code-block:: bash

        $ phinx status -e development

This command exits with code 0 if the database is up-to-date (ie. all migrations are up) or one of the following codes otherwise:

* 1: There is at least one down migration.
* 2: There is at least one missing migration.

Configuration File Parameter
----------------------------

When running Phinx from the command line, you may specify a configuration file
using the ``--configuration`` or ``-c`` parameter. In addition to YAML, the
configuration file may be the computed output of a PHP file as a PHP array:

.. code-block:: php

        <?php
            return [
                "paths" => [
                    "migrations" => "application/migrations"
                ],
                "environments" => [
                    "default_migration_table" => "phinxlog",
                    "default_database" => "dev",
                    "dev" => [
                        "adapter" => "mysql",
                        "host" => $_ENV['DB_HOST'],
                        "name" => $_ENV['DB_NAME'],
                        "user" => $_ENV['DB_USER'],
                        "pass" => $_ENV['DB_PASS'],
                        "port" => $_ENV['DB_PORT']
                    ]
                ]
            ];

Phinx auto-detects which language parser to use for files with ``*.yml`` and ``*.php`` extensions. The appropriate
parser may also be specified via the ``--parser`` and ``-p`` parameters. Anything other than ``"php"`` is treated as YAML.

When using a PHP array, you can provide a ``connection`` key with an existing PDO instance. It is also important to pass
the database name too, as Phinx requires this for certain methods such as ``hasTable()``:

.. code-block:: php

        <?php
            return [
                "paths" => [
                    "migrations" => "application/migrations"
                ),
                "environments" => [
                    "default_migration_table" => "phinxlog",
                    "default_database" => "dev",
                    "dev" => [
                        "name" => "dev_db",
                        "connection" => $pdo_instance
                    ]
                ]
            ];

Using Phinx with PHPUnit
--------------------------

Phinx can be used within your unit tests to prepare the database. You can use it programatically :

.. code-block:: php

        public function setUp ()
        {
          $app = new PhinxApplication();
          $app->setAutoExit(false);
          $app->run(new StringInput('migrate'), new NullOutput());
        }
