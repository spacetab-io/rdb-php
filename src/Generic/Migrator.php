<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Generic;

use Amp\Promise;
use Amp\Success;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Spacetab\Rdb\Creator\CreatorInterface;
use Spacetab\Rdb\Exception\RdbException;
use Spacetab\Rdb\Notifier\NullNotifier;
use Spacetab\Rdb\Notifier\NotifierInterface;
use Spacetab\Rdb\Repository\MigrationRepositoryInterface;
use function Amp\call;

final class Migrator
{
    public const FILE_TYPE_UP   = 'up';
    public const FILE_TYPE_DOWN = 'down';

    public const DEFAULT_MIGRATION_PATH = 'database/migrations';

    /**
     * @var \Spacetab\Rdb\Creator\CreatorInterface
     */
    private CreatorInterface $creator;

    /**
     * @var \Spacetab\Rdb\Repository\MigrationRepositoryInterface
     */
    private ?MigrationRepositoryInterface $repository;

    /**
     * @var \Spacetab\Rdb\Notifier\NotifierInterface
     */
    private NotifierInterface $notifier;

    /**
     * @var \League\Flysystem\AdapterInterface|null
     */
    private ?AdapterInterface $adapter;

    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    private FilesystemInterface $filesystem;

    /**
     * Migrator constructor.
     *
     * @param \Spacetab\Rdb\Repository\MigrationRepositoryInterface $repository
     * @param \Spacetab\Rdb\Creator\CreatorInterface $creator
     * @param \Spacetab\Rdb\Notifier\NotifierInterface $notifier
     * @param \League\Flysystem\AdapterInterface $adapter
     */
    public function __construct(
        CreatorInterface $creator,
        MigrationRepositoryInterface $repository = null,
        ?NotifierInterface $notifier = null,
        ?AdapterInterface $adapter = null
    )
    {
        $this->creator    = $creator;
        $this->repository = $repository;
        $this->notifier   = $notifier ?: new NullNotifier();
        $this->adapter    = $adapter  ?: new Local(self::DEFAULT_MIGRATION_PATH);
        $this->filesystem = new Filesystem($this->adapter);
    }

    /**
     * @param string $name
     * @param string|null $table
     * @param bool $create
     */
    public function create(string $name, ?string $table = null, bool $create = false): void
    {
        $this->creator->create(
            $this->filesystem, $this->notifier,
            $name, $table, $create
        );
    }

    /**
     * Returns true if migrations table exists.
     *
     * @return Promise<bool>
     * @throws \Spacetab\Rdb\Exception\RdbException
     */
    public function exists(): Promise
    {
        $this->migrationRepositoryMustBeInitialized();

        return $this->repository->repositoryExists();
    }

    /**
     * Create the migration repository if table does not exists.
     *
     * @return Promise<void>
     * @throws \Spacetab\Rdb\Exception\RdbException
     */
    public function install(): Promise
    {
        $this->migrationRepositoryMustBeInitialized();

        // Create a migration table in the
        // database if it does not exist.
        return call(function() {
            if (yield $this->repository->repositoryExists()) {
                $this->notifier->note('<fg=red>Migrations already installed</>');
            } else {
                yield $this->repository->createRepository();
                $this->notifier->note('<info>Migration table created successfully.</info>');
            }
        });
    }

    /**
     * Run the pending migrations.
     *
     * @param array $options
     * @return Promise<void>
     * @throws \Spacetab\Rdb\Exception\RdbException
     */
    public function migrate(array $options = []): Promise
    {
        $this->migrationRepositoryMustBeInitialized();

        return call(function () use ($options) {
            // Once we grab all of the migration files for the path, we will compare them
            // against the migrations that have already been run for this package then
            // run each of the outstanding migrations against a database connection.
            $files = $this->getMigrationFiles(self::FILE_TYPE_UP);

            $migrations = $this->pendingMigrations(
                $files, yield $this->repository->getRan()
            );

            // Once we have all these migrations that are outstanding we are ready to run
            // we will go ahead and run them "up". This will execute each migration as
            // an operation against a database. Then we'll return this list of them.
            yield $this->runPending($migrations, $options);
        });
    }

    /**
     * Rollback the last migration operation.
     *
     * @param array $options
     * @return Promise<void>
     * @throws \Spacetab\Rdb\Exception\RdbException
     */
    public function rollback(array $options = []): Promise
    {
        $this->migrationRepositoryMustBeInitialized();

        return call(function () use ($options) {
            // We want to pull in the last batch of migrations that ran on the previous
            // migration operation. We'll then reverse those migrations and run each
            // of them "down" to reverse the last migration "operation" which ran.
            $migrations = yield $this->getMigrationsForRollback($options);

            if (count($migrations) === 0) {
                $this->notifier->note('<info>Nothing to rollback.</info>');

                return;
            }

            yield $this->rollbackMigrations($migrations);
        });
    }

    /**
     * Rolls all of the currently applied migrations back.
     *
     * @return Promise<void>
     * @throws \Spacetab\Rdb\Exception\RdbException
     */
    public function reset(): Promise
    {
        $this->migrationRepositoryMustBeInitialized();

        return call(function () {
            // Next, we will reverse the migration list so we can run them back in the
            // correct order for resetting this database. This will allow us to get
            // the database back into its "empty" state ready for the migrations.
            $migrations = array_reverse(yield $this->repository->getRan());

            if (count($migrations) === 0) {
                $this->notifier->note('<info>Nothing to rollback.</info>');

                return;
            }

            yield $this->rollbackMigrations($migrations);
        });
    }

    /**
     * @return \Amp\Promise<array>
     * @throws \Spacetab\Rdb\Exception\RdbException
     */
    public function status(): Promise
    {
        $this->migrationRepositoryMustBeInitialized();

        return call(function () {
            $items   = [];
            $ran     = yield $this->repository->getRan();
            $batches = yield $this->repository->getMigrationBatches();
            $files   = $this->getMigrationFiles(self::FILE_TYPE_UP);

            // Check if migrations files or database migration rows exist.
            if (count($files) === 0 || count($ran) === 0) {
                $this->notifier->note('<fg=red>No migrations found.</>');
                return $items;
            }

            foreach ($files as $migration) {
                $name = $this->getMigrationName($migration);

                if (in_array($name, $ran)) {
                    $items[] = [
                        'status'  => true,
                        'ran'     => $name,
                        'batches' => $batches[$name],
                    ];
                } else {
                    $items[] = [
                        'status'  => false,
                        'ran'     => $name,
                        'batches' => null,
                    ];
                }
            }

            return $items;
        });
    }

    /**
     * @throws \Spacetab\Rdb\Exception\RdbException
     */
    private function migrationRepositoryMustBeInitialized(): void
    {
        if (!$this->repository instanceof MigrationRepositoryInterface) {
            throw RdbException::forUninitializedMigrationRepository();
        }
    }

    /**
     * Get all of the migration files in a given path.
     *
     * @param string $type
     * @return array
     */
    private function getMigrationFiles(string $type): array
    {
        $array = [];
        foreach ($this->filesystem->listContents() as $file) {
            if ($type === pathinfo($file['filename'], PATHINFO_EXTENSION)) {
                $array[] = $file;
            }
        }

        return $array;
    }

    /**
     * Run an array of migrations.
     *
     * @param  array  $migrations
     * @param  array  $options
     * @return Promise<void>
     */
    private function runPending(array $migrations, array $options = []): Promise
    {
        return call(function () use ($migrations, $options) {
            // First we will just make sure that there are any migrations to run. If there
            // aren't, we will just make a note of it to the developer so they're aware
            // that all of the migrations have been run against this database system.
            if (count($migrations) === 0) {
                $this->notifier->note('<info>Nothing to migrate.</info>');

                return;
            }

            // Next, we will get the next batch number for the migrations so we can insert
            // correct batch number in the database migrations repository when we store
            // each migration's execution. We will also extract a few of the options.
            $batch = yield $this->repository->getNextBatchNumber();

            $step = $options['step'] ?? false;

            // Once we have the array of migrations, we will spin through them and run the
            // migrations "up" so the changes are made to the databases. We'll then log
            // that the migration was run so we don't repeat it next time we execute.
            foreach ($migrations as $file) {
                yield $this->runUp($file, $batch);

                if ($step) {
                    $batch++;
                }
            }
        });
    }

    /**
     * Migration name for database.
     *
     * @param array $file
     * @return string
     */
    private function getMigrationName(array $file): string
    {
        return pathinfo($file['filename'], PATHINFO_FILENAME);
    }

    /**
     * Get the migrations for a rollback operation.
     *
     * @param  array  $options
     * @return Promise<array>
     */
    private function getMigrationsForRollback(array $options): Promise
    {
        return call(function () use ($options) {
            if (($steps = $options['step'] ?? 0) > 0) {
                return yield $this->repository->getMigrations($steps);
            }

            return yield $this->repository->getLast();
        });
    }

    /**
     * Rollback the given migrations.
     *
     * @param  array  $migrations
     * @return Promise<void>
     */
    private function rollbackMigrations(array $migrations): Promise
    {
        $files = $this->getMigrationFiles(self::FILE_TYPE_DOWN);
        $check = array_map(fn($x) => $this->getMigrationName($x), $files);

        return call(function () use ($migrations, $files, $check) {
            foreach ($migrations as $migration) {
                if (!in_array($migration, $check, true)) {
                    $this->notifier->note("<fg=red>Migration not found:</> {$migration}");
                    continue;
                }

                foreach ($files as $file) {
                    if ($this->getMigrationName($file) === $migration) {
                        yield $this->runDown($file);
                    }
                }
            }
        });
    }

    /**
     * Run "down" a migration instance.
     *
     * @param  array $file
     * @return Promise<void>
     */
    private function runDown(array $file): Promise
    {
        return call(function () use ($file) {
            $this->notifier->note("<comment>Rolling back:</comment> {$file['basename']}");

            yield $this->runMigration($file);

            // Once we have successfully run the migration "down" we will remove it from
            // the migration repository so it will be considered to have not been run
            // by the application then will be able to fire by any later operation.
            yield $this->repository->delete($this->getMigrationName($file));

            $this->notifier->note("<info>Rolled back:</info>  {$file['basename']}");
        });
    }

    /**
     * Run "up" a migration instance.
     *
     * @param array  $file
     * @param int   $batch
     *
     * @return Promise<void>
     */
    private function runUp(array $file, int $batch): Promise
    {
        return call(function () use ($file, $batch) {
            $this->notifier->note("<comment>Migrating:</comment> {$file['basename']}");

            yield $this->runMigration($file);

            // Once we have run a migrations class, we will log that it was run in this
            // repository so that we don't try to run it next time we do a migration
            // in the application. A migration repository keeps the migrate order.
            yield $this->repository->log($this->getMigrationName($file), $batch);

            $this->notifier->note("<info>Migrated:</info> {$file['basename']}");
        });
    }

    /**
     * Run a migration inside a transaction if the database supports it.
     *
     * @param array $file
     * @return Promise<void>
     */
    private function runMigration(array $file): Promise
    {
        return call(function () use ($file) {
            $contents = $this->filesystem->read($file['path']);

            if ($contents) {
                yield $this->repository->transaction($contents);
            }
        });
    }

    /**
     * Get the migration files that have not yet run.
     *
     * @param  array  $files
     * @param  array  $ran
     *
     * @return array
     */
    private function pendingMigrations(array $files, array $ran): array
    {
        $array = [];
        foreach ($files as $file) {
            if (! in_array($this->getMigrationName($file), $ran, true)) {
                $array[] = $file;
            }
        }

        return $array;
    }
}
