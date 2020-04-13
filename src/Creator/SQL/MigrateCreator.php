<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Creator\SQL;

use League\Flysystem\FilesystemInterface;
use Spacetab\Rdb\Creator\CreatorInterface;
use Spacetab\Rdb\Generic\Migrator;
use Spacetab\Rdb\Notifier\NotifierInterface;

final class MigrateCreator implements CreatorInterface
{
    private const MIGRATION_BLANK_STUB = <<<STUB
    -- migration: {name}

    STUB;

    private const MIGRATION_CREATE_STUB = <<<STUB
    -- migration: {name}

    CREATE TABLE {table} (
      id SERIAL PRIMARY KEY,
      
    );
    STUB;

    private const MIGRATION_DOWN_STUB = <<<STUB
    -- migration: {name}

    DROP TABLE IF EXISTS {table};
    STUB;

    private const MIGRATION_UPDATE_STUB = <<<STUB
    -- migration: {name}

    ALTER TABLE {table}
    STUB;

    /**
     * Create a new migration.
     *
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \Spacetab\Rdb\Notifier\NotifierInterface $notifier
     * @param string $name
     * @param mixed ...$options
     * @return void
     */
    public function create(FilesystemInterface $filesystem, NotifierInterface $notifier, string $name, ...$options): void
    {
        [$table, $create]  = $options;

        foreach ($this->getMigrations($table, $create) as $type => $stub) {
            $filesystem->put(
                $filename = $this->getFilename($name, $type),
                $this->populateStub($filename, $stub, $table)
            );

            $notifier->note("<comment>Migration</comment> {$filename} <comment>created</comment>");
        }
    }

    /**
     * Get the migrations.
     *
     * @param  string $table
     * @param  bool $create
     * @return array
     */
    private function getMigrations(?string $table = null, bool $create = false): array
    {
        if (is_null($table)) {
            return [
                Migrator::FILE_TYPE_UP   => self::MIGRATION_BLANK_STUB,
                Migrator::FILE_TYPE_DOWN => self::MIGRATION_BLANK_STUB,
            ];
        }

        $first  = [Migrator::FILE_TYPE_UP => self::MIGRATION_CREATE_STUB, Migrator::FILE_TYPE_DOWN => self::MIGRATION_DOWN_STUB];
        $second = [Migrator::FILE_TYPE_UP => self::MIGRATION_UPDATE_STUB, Migrator::FILE_TYPE_DOWN => self::MIGRATION_UPDATE_STUB];

        return $create ? $first : $second;
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param  string  $filename
     * @param  string  $stub
     * @param  string  $table
     * @return string
     */
    private function populateStub(string $filename, string $stub, ?string $table = null): string
    {
        $search = ['{name}', '{table}'];
        $replace = [$filename, $table ?: 'dummy_table'];

        return str_replace($search, $replace, $stub);
    }

    /**
     * Get the migrations file names.
     *
     * @param  string $name
     * @param string $type
     * @return string
     */
    private function getFilename(string $name, string $type): string
    {
        return $this->getDatePrefix() . "_{$name}.{$type}.sql";
    }

    /**
     * Get the date prefix for the migration.
     *
     * @return string
     */
    private function getDatePrefix(): string
    {
        return date('Y_m_d_His');
    }
}
