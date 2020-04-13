<?php

declare(strict_types=1);

namespace Spacetab\Rdb;

use League\Flysystem\AdapterInterface;
use Spacetab\Rdb\Driver\DriverInterface;
use Spacetab\Rdb\Generic\Migrator;
use Spacetab\Rdb\Generic\Seeder;
use Spacetab\Rdb\Notifier\NullNotifier;
use Spacetab\Rdb\Notifier\NotifierInterface;

final class Rdb
{
    /**
     * @var \Spacetab\Rdb\Notifier\NotifierInterface
     */
    private NotifierInterface $notifier;

    /**
     * @var \Spacetab\Rdb\Driver\DriverInterface
     */
    private DriverInterface $driver;

    /**
     * Rdb constructor.
     *
     * @param \Spacetab\Rdb\Driver\DriverInterface $driver
     * @param \Spacetab\Rdb\Notifier\NotifierInterface $notifier
     */
    public function __construct(DriverInterface $driver, ?NotifierInterface $notifier = null)
    {
        $this->driver   = $driver;
        $this->notifier = $notifier ?: new NullNotifier();
    }

    /**
     * @param \League\Flysystem\AdapterInterface|null $adapter
     * @return \Spacetab\Rdb\Generic\Migrator
     */
    public function getMigrator(?AdapterInterface $adapter = null): Migrator
    {
        return new Migrator(
            $this->driver->getMigrationCreator(),
            $this->driver->getMigrationRepository(),
            $this->notifier,
            $adapter
        );
    }

    /**
     * @param \League\Flysystem\AdapterInterface|null $adapter
     * @return \Spacetab\Rdb\Generic\Seeder
     */
    public function getSeeder(?AdapterInterface $adapter = null): Seeder
    {
        return new Seeder(
            $this->driver->getSeedCreator(),
            $this->driver->getSeederRepository(),
            $this->notifier,
            $adapter
        );
    }
}
