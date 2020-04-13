<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Driver\SQL;

use Spacetab\Rdb\Repository\MigrationRepositoryInterface;
use Spacetab\Rdb\Repository\SQL\SeederRepository;

class Unconnected extends AbstractDriver
{
    public function getMigrationRepository(): ?MigrationRepositoryInterface
    {
        return null;
    }

    public function getSeederRepository(): ?SeederRepository
    {
        return null;
    }
}
