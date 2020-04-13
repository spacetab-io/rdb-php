<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Driver;

use Amp\Postgres\ConnectionConfig as PostgresConnection;
use Amp\Mysql\ConnectionConfig as MysqlConnection;
use Amp\Postgres;
use Amp\Mysql;
use Spacetab\Rdb\Driver\SQL\Unconnected;
use Spacetab\Rdb\Exception\RdbException;

final class Factory
{
    /**
     * @var array<\Amp\Sql\Pool>
     */
    private array $pool;

    /**
     * @param string $type
     * @param string $connection
     * @return \Spacetab\Rdb\Driver\SQL\Mysql|\Spacetab\Rdb\Driver\SQL\Postgres
     * @throws \Spacetab\Rdb\Exception\RdbException
     */
    public function connect(string $type, string $connection): DriverInterface
    {
        switch ($type) {
            case DriverInterface::POSTGRES:
                return new SQL\Postgres(
                    $this->pool[] = Postgres\pool(PostgresConnection::fromString($connection))
                );
            case DriverInterface::MYSQL:
                return new SQL\Mysql(
                    $this->pool[] = Mysql\pool(MysqlConnection::fromString($connection))
                );
        }

        throw RdbException::forUnsupportedDriver($type);
    }

    /**
     * @param string $type
     * @return \Spacetab\Rdb\Driver\DriverInterface
     * @throws \Spacetab\Rdb\Exception\RdbException
     */
    public function unconnected(string $type): DriverInterface
    {
        switch ($type) {
            case DriverInterface::POSTGRES:
            case DriverInterface::MYSQL:
                return new Unconnected();
        }

        throw RdbException::forUnsupportedDriver($type);
    }

    public function close(): void
    {
        foreach ($this->pool as $pool) {
            $pool->close();
        }
    }
}
