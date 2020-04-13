<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Driver;

use Amp\PHPUnit\AsyncTestCase;
use Spacetab\Rdb\Driver\DriverInterface;
use Spacetab\Rdb\Driver\Factory;
use Spacetab\Rdb\Driver;
use Spacetab\Rdb\Exception\RdbException;

class FactoryTest extends AsyncTestCase
{
    public function testCreatesObjectsWithDatabaseConnection()
    {
        $factory = new Factory();
        $postgres = $factory->connect(DriverInterface::POSTGRES, 'host=_localhost user=_username');
        $mysql = $factory->connect(DriverInterface::MYSQL, 'host=_localhost user=_username');
        $factory->close();

        $this->assertInstanceOf(Driver\SQL\Postgres::class, $postgres);
        $this->assertInstanceOf(Driver\SQL\Mysql::class, $mysql);
    }

    public function testCreatesObjectsWithUnsupportedDatabaseConnection()
    {
        $this->expectException(RdbException::class);
        $this->expectExceptionMessage('Driver [__unsupported] not supported.');

        $factory = new Factory();
        $factory->connect('__unsupported', '');
    }

    public function testCreatesObjectsForUnconnectedCases()
    {
        $factory = new Factory();
        $postgres = $factory->unconnected(DriverInterface::POSTGRES);
        $mysql = $factory->unconnected(DriverInterface::MYSQL);

        $this->assertInstanceOf(Driver\SQL\Unconnected::class, $postgres);
        $this->assertInstanceOf(Driver\SQL\Unconnected::class, $mysql);
    }

    public function testCreatesObjectsForUnconnectedCasesIfDriverNotFound()
    {
        $this->expectException(RdbException::class);
        $this->expectExceptionMessage('Driver [__unsupported] not supported.');

        $factory = new Factory();
        $factory->unconnected('__unsupported');
    }
}
