<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Integration\Postgres;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Postgres;
use Amp\Process\Process;
use Amp\Promise;
use Amp\Sql\Pool;
use function Amp\call;
use Amp\ByteStream;

abstract class DefaultTestCase extends AsyncTestCase
{
    protected const MIGRATIONS_PATH = __DIR__ . '/../../Stub/DefaultCase/migrations';
    protected const SEEDS_PATH = __DIR__ . '/../../Stub/DefaultCase/seeds';

    protected Pool $pool;
    protected bool $provideConnectFlag = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pool = Postgres\pool(
            Postgres\ConnectionConfig::fromString($this->getPostgresConnectionString())
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->pool->close();
    }

    protected function migrateUpThroughConsole(bool $seed = false, bool $step = false): Promise
    {
        $command = $this->getCommand([
            $this->getBinary(), 'migrate:up', '-i',
            '--migrate-path', self::MIGRATIONS_PATH
        ]);

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
        $command = $this->getCommand([
            $this->getBinary(), 'migrate:' . $cmdName,
            '--path', self::MIGRATIONS_PATH
        ]);

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
            $process = new Process($this->getCommand([
                $this->getBinary(), 'migrate:reset',
                '--path', self::MIGRATIONS_PATH
            ]));

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
            $process = new Process($this->getCommand([
                $this->getBinary(), 'migrate:status',
                '--path', self::MIGRATIONS_PATH
            ]));

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
    protected function getPostgresConnectionString(): string
    {
        $host   = getenv('PHPUNIT_RDB_PG_HOST') ?: 'localhost';
        $user   = getenv('PHPUNIT_RDB_PG_USER') ?: getenv('USER');
        $port   = getenv('PHPUNIT_RDB_PG_PORT') ?: 5432;
        $dbName = getenv('PHPUNIT_RDB_PG_DBNAME') ?: getenv('USER');
        $pwd    = getenv('PHPUNIT_RDB_PG_PWD') ?: '';

        return sprintf('host=%s port=%d dbname=%s user=%s password=%s', $host, (int) $port, $dbName, $user, $pwd);
    }

    protected function getBinary(): string
    {
        return 'bin/rdb';
    }

    protected function getCommand(array $command)
    {
        if ($this->provideConnectFlag) {
            return array_merge($command, ['--connect', $this->getPostgresConnectionString()]);
        }

        return $command;
    }
}
