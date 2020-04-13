<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Exception;

class RdbException extends \Exception
{
    public static function forUnsupportedDriver(string $driverName): self
    {
        return new self("Driver [$driverName] not supported.");
    }

    public static function forUninitializedMigrationRepository()
    {
        return new self('MigrationRepository must be initialized to run this command.');
    }

    public static function forUninitializedSeedRepository()
    {
        return new self('SeederRepository must be initialized to run this command.');
    }
}
