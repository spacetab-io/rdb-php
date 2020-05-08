<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Repository\SQL;

use Amp\Promise;
use Amp\Sql\Pool as PoolInterface;
use Spacetab\Rdb\Repository\MigrationRepositoryInterface;
use function Amp\call;

abstract class AbstractMigrationRepository implements MigrationRepositoryInterface
{
    /**
     * @var string
     */
    protected string $table;

    /**
     * @var \Amp\Sql\Pool
     */
    protected PoolInterface $pool;

    /**
     * AbstractMigrationRepository constructor.
     *
     * @param \Amp\Sql\Pool $pool
     * @param string $table
     */
    public function __construct(PoolInterface $pool, string $table = self::DEFAULT_TABLE)
    {
        $this->pool  = $pool;
        $this->table = $table;
    }

    /**
     * Get the completed migrations.
     *
     * @return Promise<array>
     */
    public function getRan(): Promise
    {
        return call(function () {
            /** @var \Amp\Postgres\ResultSet $result */
            $result = yield $this->pool->query("select migration from {$this->table} order by batch, migration");

            $array = [];
            while (yield $result->advance()) {
                $array[] = $result->getCurrent()['migration'];
            }

            return $array;
        });
    }

    /**
     * Get list of migrations.
     *
     * @param  int $steps
     * @return Promise<array>
     */
    public function getMigrations(int $steps): Promise
    {
        $sql = "select migration from {$this->table} 
                where batch >= 1 
                order by batch, migration desc
                limit :limit";

        return call(function () use ($sql, $steps) {
            /** @var \Amp\Sql\Statement $statement */
            $statement = yield $this->pool->prepare($sql);
            /** @var \Amp\Sql\ResultSet $result */
            $result = yield $statement->execute(['limit' => $steps]);

            $array = [];
            while (yield $result->advance()) {
                $array[] = $result->getCurrent()['migration'];
            }

            return $array;
        });
    }

    /**
     * Get the last migration batch.
     *
     * @return Promise<array>
     */
    public function getLast(): Promise
    {
        $sql = "select migration from migrations 
                where batch = (select max(batch) from migrations) 
                order by migration desc";

        return call(function () use ($sql) {
            /** @var \Amp\Sql\Statement $statement */
            $statement = yield $this->pool->prepare($sql);
            /** @var \Amp\Sql\ResultSet $result */
            $result = yield $statement->execute();

            $array = [];
            while (yield $result->advance()) {
                $array[] = $result->getCurrent()['migration'];
            }

            return $array;
        });
    }

    /**
     * Get the completed migrations with their batch numbers.
     *
     * @return Promise<array>
     */
    public function getMigrationBatches(): Promise
    {
        $sql = "select * from {$this->table} order by batch, migration";

        return call(function () use ($sql) {
            /** @var \Amp\Sql\Statement $statement */
            $statement = yield $this->pool->prepare($sql);
            /** @var \Amp\Sql\ResultSet $result */
            $result = yield $statement->execute();

            $array = [];
            while (yield $result->advance()) {
                $item = $result->getCurrent();
                $array[$item['migration']] = $item['batch'];
            }

            return $array;
        });
    }

    /**
     * Log that a migration was run.
     *
     * @param  string $file
     * @param  int $batch
     * @return Promise<void>
     */
    public function log(string $file, int $batch): Promise
    {
        $sql = "insert into {$this->table} (migration, batch) values (:file, :batch)";

        return call(function () use ($sql, $file, $batch) {
            /** @var \Amp\Sql\Statement $statement */
            $statement = yield $this->pool->prepare($sql);
            yield $statement->execute(compact('file', 'batch'));
        });
    }

    /**
     * Remove a migration from the log.
     *
     * @param  string $migration
     * @return Promise<void>
     */
    public function delete(string $migration): Promise
    {
        return call(function () use ($migration) {
            /** @var \Amp\Sql\Statement $statement */
            $statement = yield $this->pool->prepare("delete from {$this->table} where migration = :migration");
            yield $statement->execute(compact('migration'));
        });
    }

    /**
     * Get the next migration batch number.
     *
     * @return Promise<int>
     */
    public function getNextBatchNumber(): Promise
    {
        return call(function () {
            /** @var \Amp\Sql\ResultSet $result */
            $result = yield $this->pool->query("select max(batch) from {$this->table}");
            yield $result->advance();

            return ((int) $result->getCurrent()['max']) + 1;
        });
    }

    /**
     * Create the migration repository data store.
     *
     * @return Promise<void>
     */
    public function createRepository(): Promise
    {
        $sql = "CREATE TABLE {$this->table} (
            migration Varchar (255) NOT NULL,
            batch Integer NOT NULL
        );";

        return call(fn() => yield $this->pool->execute($sql));
    }

    /**
     * Wraps the queries inside callback into transaction.
     *
     * @param string $sql
     * @return Promise<void>
     */
    public function transaction(string $sql): Promise
    {
        return call(function () use ($sql) {
            /** @var \Amp\Sql\Transaction $transaction */
//            $transaction = yield $this->pool->beginTransaction();
//            try {
//                yield $transaction->query($sql);
//                yield $transaction->commit();
//            } catch (\Throwable $e) {
//                yield $transaction->rollback();
//                throw $e;
//            }
            yield $this->pool->query($sql);
        });
    }

    /**
     * @param string $sql
     * @return \Amp\Promise<bool>
     */
    protected function checkIfRepositoryExists(string $sql): Promise
    {
        return call(function () use ($sql) {
            /** @var \Amp\Sql\Statement $statement */
            $statement = yield $this->pool->prepare($sql);

            /** @var \Amp\Sql\ResultSet $result */
            $result = yield $statement->execute(['tableName' => $this->table]);
            yield $result->advance();

            return $result->getCurrent()['count'] > 0;
        });
    }
}
