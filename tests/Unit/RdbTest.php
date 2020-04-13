<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit;

use Amp\PHPUnit\AsyncTestCase;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use Spacetab\Rdb\Driver\DriverInterface;
use Spacetab\Rdb\Generic\Migrator;
use Spacetab\Rdb\Generic\Seeder;
use Spacetab\Rdb\Rdb;

class RdbTest extends AsyncTestCase
{
    public function testConstructorInitializingWithOneArgument()
    {
        $rdb = new Rdb($this->createMock(DriverInterface::class));
        $this->assertInstanceOf(Rdb::class, $rdb);
    }

    public function testMigratorGetterMustWorkWithoutArguments()
    {
        $rdb = new Rdb($this->createMock(DriverInterface::class));
        $migrator = $rdb->getMigrator($this->createMock(AdapterInterface::class));

        $this->assertInstanceOf(Migrator::class, $migrator);
    }

    public function testSeederGetterMustWorkWithoutArguments()
    {
        $rdb = new Rdb($this->createMock(DriverInterface::class));
        $seeder = $rdb->getSeeder($this->createMock(AdapterInterface::class));

        $this->assertInstanceOf(Seeder::class, $seeder);
    }

    public function testMigratorGetterMustAcceptAnyFlysystemAdapter()
    {
        $rdb = new Rdb($this->createMock(DriverInterface::class));
        $migrator = $rdb->getMigrator($this->createMock(AdapterInterface::class));

        $this->assertInstanceOf(Migrator::class, $migrator);
    }

    public function testSeederGetterMustAcceptAnyFlysystemAdapter()
    {
        $rdb = new Rdb($this->createMock(DriverInterface::class));
        $seeder = $rdb->getSeeder($this->createMock(AdapterInterface::class));

        $this->assertInstanceOf(Seeder::class, $seeder);
    }
}
