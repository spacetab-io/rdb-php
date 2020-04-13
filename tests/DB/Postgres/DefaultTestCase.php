<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\DB\Postgres;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Postgres;
use Amp\Process\Process;
use Amp\Promise;
use Amp\Sql\Pool;
use League\Flysystem\Adapter\Local;
use Spacetab\Rdb\Generic\Migrator;
use Spacetab\Rdb\Rdb;
use Spacetab\Rdb\Driver;
use function Amp\call;
use Amp\ByteStream;

abstract class DefaultTestCase extends AsyncTestCase
{
    protected const MIGRATIONS_PATH = __DIR__ . '/../../Stub/DefaultCase/migrations';
    protected const SEEDS_PATH = __DIR__ . '/../../Stub/DefaultCase/seeds';

    protected Pool $pool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pool = Postgres\pool(
            Postgres\ConnectionConfig::fromString($this->getConnectionString())
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->pool->close();
    }

    protected function migrateUpThroughConsole(bool $seed = false, bool $step = false): Promise
    {
        $command = [
            'bin/rdb', 'migrate:up', '-i',
            '--connect', $this->getConnectionString(),
            '--migrate-path', self::MIGRATIONS_PATH
        ];

        switch (true) {
            case $seed:
                $command = array_merge($command, ['--seed', '--seed-path', self::SEEDS_PATH]);
                break;
            case $step:
                $command = array_merge($command, ['--step']);
                break;
        }

        return call(function () use ($command) {
            $process = new Process($command);

            yield $process->start();
            $contents = yield ByteStream\buffer($process->getStdout());

            $this->assertMatchesRegularExpression('/Migrating: .*\.sql/', $contents);
            $this->assertMatchesRegularExpression('/Migrated: .*\.sql/', $contents);

            return $contents;
        });
    }

    protected function migrateDownThroughConsole(?int $step = null, string $cmdName = 'down'): Promise
    {
        $command = [
            'bin/rdb', 'migrate:' . $cmdName,
            '--connect', $this->getConnectionString(),
            '--path', self::MIGRATIONS_PATH
        ];

        if ($step) {
            $command = array_merge($command, ['--step', $step]);
        }

        return call(function () use ($command) {
            $process = new Process($command);

            yield $process->start();
            $contents = yield ByteStream\buffer($process->getStdout());

            $this->assertMatchesRegularExpression('/Rolling back: .*\.sql/', $contents);
            $this->assertMatchesRegularExpression('/Rolled back: .*\.sql/', $contents);

            return $contents;
        });
    }

    /**
     * @return \Amp\Promise<array>
     */
    protected function migrateResetThroughConsole(): Promise
    {
        return call(function () {
            $process = new Process([
                'bin/rdb', 'migrate:reset',
                '--connect', $this->getConnectionString(),
                '--path', self::MIGRATIONS_PATH
            ]);

            yield $process->start();

            $contents = yield ByteStream\buffer($process->getStdout());

            $this->assertMatchesRegularExpression('/Rolling back: .*\.sql/', $contents);
            $this->assertMatchesRegularExpression('/Rolled back: .*\.sql/', $contents);

            return $contents;
        });
    }

    /**
     * @return \Amp\Promise<array>
     */
    protected function migrateStatusThroughConsole(): Promise
    {
        return call(function () {
            $process = new Process([
                'bin/rdb', 'migrate:status',
                '--connect', $this->getConnectionString(),
                '--path', self::MIGRATIONS_PATH
            ]);

            yield $process->start();

            $contents = yield ByteStream\buffer($process->getStdout());

            $this->assertMatchesRegularExpression('/create_test1_table/', $contents);
            $this->assertMatchesRegularExpression('/create_test2_table/', $contents);

            return $contents;
        });
    }

    /**
     * @return \Amp\Promise
     */
    protected function clean(): Promise
    {
        return call(function () {
            yield $this->pool->execute('drop table if exists migrations;');
            yield $this->pool->execute('drop table if exists test1;');
            yield $this->pool->execute('drop table if exists test2;');
        });
    }

    /**
     * @param string $table
     * @return \Amp\Promise<array>
     */
    protected function getTableContents(string $table): Promise
    {
        return call(function () use ($table) {
            /** @var \Amp\Postgres\ResultSet $result */
            $result = yield $this->pool->query("select * from {$table}");

            $array = [];
            while (yield $result->advance()) {
                $array[] = $result->getCurrent();
            }

            return $array;
        });
    }

    /**
     * @return string
     */
    protected function getConnectionString(): string
    {
        $host   = getenv('PHPUNIT_RDB_HOST') ?: 'localhost';
        $user   = getenv('PHPUNIT_RDB_USER') ?: getenv('USER');
        $port   = getenv('PHPUNIT_RDB_PORT') ?: 5432;
        $dbName = getenv('PHPUNIT_RDB_DBNAME') ?: getenv('USER');
        $pwd    = getenv('PHPUNIT_RDB_PWD') ?: '';

        return sprintf('host=%s port=%d dbname=%s user=%s password=%s', $host, (int) $port, $dbName, $user, $pwd);
    }
}
