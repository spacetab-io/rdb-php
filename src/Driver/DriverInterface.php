<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Driver;

use Spacetab\Rdb\Creator\CreatorInterface;
use Spacetab\Rdb\Repository\MigrationRepositoryInterface;
use Spacetab\Rdb\Repository\SQL\SeederRepository;

interface DriverInterface
{
    public const POSTGRES = 'pgsql';
    public const MYSQL    = 'mysql';

    public function getMigrationRepository(): ?MigrationRepositoryInterface;
    public function getMigrationCreator(): CreatorInterface;

    public function getSeedCreator(): CreatorInterface;
    public function getSeederRepository(): ?SeederRepository;
}
