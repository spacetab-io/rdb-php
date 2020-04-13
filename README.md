Async PHP migrations & seeds
============================

[![CircleCI](https://circleci.com/gh/spacetab-io/rdb-php/tree/master.svg?style=svg)](https://circleci.com/gh/spacetab-io/rdb-php/tree/master)
[![codecov](https://codecov.io/gh/spacetab-io/rdb-php/branch/master/graph/badge.svg)](https://codecov.io/gh/spacetab-io/rdb-php)

Dead-simple PHP migrations controlled from code.

## Features

* Asynchronous migrations build with <strong><a href="https://amphp.org/">Amp</a></strong>.
* Developed to use from **code** and **cli**.
* Developed to use any database with these tool. Currently supported official [Amp Postgres](https://github.com/amphp/postgres) & [MySQL](https://github.com/amphp/mysql) clients. 
* You can extend console commands to use tool with your custom config definitions instead of always use `--connect` flag.
* You can use migrations/seeds files from anywhere and with any paths.

## Installation

Install tool:

```bash
composer require spacetab-io/rdb
```

and install database client, like this:

```bash
composer require amphp/postgres
```

## Usage

```php
use Spacetab\Rdb\Notifier\StdoutNotifier;
use Spacetab\Rdb\Rdb;
use Spacetab\Rdb\Driver;
use Amp\Postgres;
use Amp\Postgres\ConnectionConfig;

Amp\Loop::run(function () {
    $config = ConnectionConfig::fromString('host=localhost user=root dbname=test');
    /** @var Postgres\Pool $pool */
    $pool = Postgres\pool($config);

    $driver   = new Driver\SQL\Postgres($pool);
    $rdb      = new Rdb($driver, new StdoutNotifier()); // Optional. By default notifications is muted.
    $migrator = $rdb->getMigrator();

    yield $migrator->install();
    yield $migrator->migrate();
    yield $rdb->getSeeder()->run();
});
```

## Usage from cli

```bash
vendor/bin/rdb list
```

```text
Rdb – dead-simple async PHP migrations controlled from code. (v1.0).

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  help             Displays help for a command
  list             Lists commands
 make
  make:migration   Create a new migration files
  make:seed        Create a new seeder file
 migrate
  migrate:down     [migrate:rollback] Rollback the last database migration
  migrate:install  Create the migration repository
  migrate:refresh  Reset and re-run all migrations
  migrate:reset    Rollback all database migrations
  migrate:status   Show the status of each migration
  migrate:up       Run the database migrations
 seed
  seed:run         Seed the database with records
```

## Depends

* \>= PHP 7.4
* Composer for install package

## Tests

* Unit `vendor/bin/phpunit -c phpunit-unit.xml`
* DB `vendor/bin/phpunit -c phpunit-db.xml`. Accept ENV's: `PHPUNIT_RDB_PG_HOST`, `PHPUNIT_RDB_PG_PORT`, 
`PHPUNIT_RDB_PG_DBNAME`, `PHPUNIT_RDB_PG_USER`, `PHPUNIT_RDB_PG_PWD`

## License

The MIT License

Copyright © 2020 spacetab.io, Inc. https://spacetab.io

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

