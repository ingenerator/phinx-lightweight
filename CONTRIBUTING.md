# How to contribute to phinx-lightweight

Contributions to improve phinx-lightweight are welcome. Issues and pull requests should be submitted through our github
repository.

Before you start, please bear in mind the following:

* We are explicitly aiming to keep this lightweight. Features that are not critical to writing & running database
  migrations are unlikely to be merged.

* Any changes that add new external dependencies will probably not be merged.

* Drivers for other databases may be considered, but we are not likely to reinstate platform portability for migrations
  themselves.

* We fundamentally believe that migrations should modify the database with explicit SQL statements, not through an
  abstraction layer.

* We fundamentally believe that database migrations should be roll-forwards-only.


If you're unsure whether your proposed contribution is in scope for this package, please discuss it with us in an issue
before you start work.

All contributions should be accompanied by unit tests wherever possible.
