<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Driver\SQL;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sql\Pool as PoolInterface;
use Spacetab\Rdb\Creator\SQL\MigrateCreator;
use Spacetab\Rdb\Creator\SQL\SeedCreator;
use Spacetab\Rdb\Driver;
use Spacetab\Rdb\Repository\SQL\PostgresMigrationRepository;
use Spacetab\Rdb\Repository\SQL\SeederRepository;

class PostgresTest extends AsyncTestCase
{
    public function testConstructorInitializedWithOneArgument()
    {
        $driver = new Driver\SQL\Postgres($this->createMock(PoolInterface::class));
        $this->assertInstanceOf(Driver\SQL\Postgres::class, $driver);
    }

    public function testMigrationRepositoryGetterReturnsMysqlObject()
    {
        $driver = new Driver\SQL\Postgres($this->createMock(PoolInterface::class));
        $this->assertInstanceOf(PostgresMigrationRepository::class, $driver->getMigrationRepository());
    }

    public function testSeederRepositoryGetterReturnsSeederObject()
    {
        $driver = new Driver\SQL\Postgres($this->createMock(PoolInterface::class));
        $this->assertInstanceOf(SeederRepository::class, $driver->getSeederRepository());
    }

    public function testMigrationCreatorGetterReturnsCorrectObject()
    {
        $driver = new Driver\SQL\Postgres($this->createMock(PoolInterface::class));
        $this->assertInstanceOf(MigrateCreator::class, $driver->getMigrationCreator());
    }

    public function testSeedCreatorGetterReturnsCorrectObject()
    {
        $driver = new Driver\SQL\Postgres($this->createMock(PoolInterface::class));
        $this->assertInstanceOf(SeedCreator::class, $driver->getSeedCreator());
    }
}
