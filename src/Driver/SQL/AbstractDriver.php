<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Driver\SQL;

use Spacetab\Rdb\Creator\CreatorInterface;
use Spacetab\Rdb\Creator\SQL\MigrateCreator;
use Spacetab\Rdb\Creator\SQL\SeedCreator;
use Spacetab\Rdb\Driver\DriverInterface;

abstract class AbstractDriver implements DriverInterface
{
    public function getMigrationCreator(): CreatorInterface
    {
        return new MigrateCreator();
    }

    public function getSeedCreator(): CreatorInterface
    {
        return new SeedCreator();
    }
}
