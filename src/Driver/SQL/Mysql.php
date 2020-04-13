<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Driver\SQL;

use Amp\Sql\Pool as PoolInterface;
use Spacetab\Rdb\Repository\MigrationRepositoryInterface;
use Spacetab\Rdb\Repository\SQL\MysqlMigrationRepository;
use Spacetab\Rdb\Repository\SQL\SeederRepository;

class Mysql extends AbstractDriver
{
    /**
     * @var \Amp\Sql\Pool
     */
    private PoolInterface $pool;

    /**
     * @var string
     */
    private string $table;

    /**
     * Postgres constructor.
     *
     * @param \Amp\Sql\Pool $pool
     * @param string $table
     */
    public function __construct(PoolInterface $pool, string $table = MigrationRepositoryInterface::DEFAULT_TABLE)
    {
        $this->pool  = $pool;
        $this->table = $table;
    }

    public function getMigrationRepository(): MigrationRepositoryInterface
    {
        return new MysqlMigrationRepository($this->pool, $this->table);
    }

    public function getSeederRepository(): SeederRepository
    {
        return new SeederRepository($this->pool);
    }
}
