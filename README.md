# :axe: phinx-lightweight :axe: (VERY) Simple PHP Database Migrations

phinx-lightweight is a tool to manage and apply a sequence of migrations to your database. Migrations are written as
PHP classes, and can execute SQL statements, compare & coalesce data, pull in constants and values from your
application code, etc.

phinx-lightweight has (and will always have) the bare minimum of third-party dependencies, and can be used with any
app or framework.

### DO NOT USE THIS PACKAGE WITHOUT READING THIS

This is a permanent hard fork from [robmorgan/phinx](https://github.com/cakephp/phinx) at v0.9.3. If you are migrating
from phinx, you should be aware that we have made fundamental changes to the upstream version, notably:

* Migration classes will usually execute explicit SQL commands to modify the database. We have removed Phinx's entire
  database modelling layer and many schema manipulation helper functions. You can, of course, implement any helper
  functions that are useful for your app e.g. by using a custom base migration class.

* phinx-lightweight only supports MySQL (or MySQL compatible) databases over PDO, all other drivers have been removed.

* There is no mechanism for automatically rolling back migrations. Rolling back database migrations is hard, and often
  not as simple as applying the opposite statements in reverse. Rolling back also loses history and can leave the
  database in an unexpected state for the next migration. We enforce a roll-forwards-only strategy : if a migration
  does not work as expected in production you will need to write, commit, and deploy a new migration to get you from
  where you are now to where you want to be.

* We have removed functionality for seeding database content. Seeds should not be running in production and are
  (in our opinion) not the concern of a database migration tool. If you want to seed a development database, use one of
  the tools designed for that purpose.

See the changelog for more information on removed functionality.

### Documentation

See the information in the `docs` folder. Note that the online documentation for phinx covers a number of features that
are not present in phinx-lightweight.

---

### License

(The MIT license)

Copyright (c) 2017 Rob Morgan
Copyright (c) 2022 inGenerator Ltd

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit
persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
