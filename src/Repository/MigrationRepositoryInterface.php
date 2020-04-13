<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Repository;

use Amp\Promise;

interface MigrationRepositoryInterface
{
    public const DEFAULT_TABLE = 'migrations';

    /**
     * Get the completed migrations.
     *
     * @return Promise
     */
    public function getRan(): Promise;

    /**
     * Get list of migrations.
     *
     * @param  int  $steps
     * @return Promise
     */
    public function getMigrations(int $steps): Promise;

    /**
     * Get the last migration batch.
     *
     * @return Promise
     */
    public function getLast(): Promise;

    /**
     * Get the completed migrations with their batch numbers.
     *
     * @return Promise
     */
    public function getMigrationBatches(): Promise;

    /**
     * Log that a migration was run.
     *
     * @param  string  $file
     * @param  int  $batch
     * @return Promise
     */
    public function log(string $file, int $batch): Promise;

    /**
     * Remove a migration from the log.
     *
     * @param  string  $migration
     * @return Promise
     */
    public function delete(string $migration): Promise;

    /**
     * Get the next migration batch number.
     *
     * @return Promise<int>
     */
    public function getNextBatchNumber(): Promise;

    /**
     * Create the migration repository data store.
     *
     * @return Promise<void>
     */
    public function createRepository(): Promise;

    /**
     * Determine if the migration repository exists.
     *
     * @return Promise<bool>
     */
    public function repositoryExists(): Promise;

    /**
     * Wraps the sql string into transaction.
     *
     * @param string $sql
     *
     * @return Promise<void>
     */
    public function transaction(string $sql): Promise;
}
