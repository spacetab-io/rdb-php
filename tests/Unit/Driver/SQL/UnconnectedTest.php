<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Driver\SQL;

use Amp\PHPUnit\AsyncTestCase;
use Spacetab\Rdb\Driver;

class UnconnectedTest extends AsyncTestCase
{
    public function testConstructorCanBeInitializedWithoutArguments()
    {
        $driver = new Driver\SQL\Unconnected();

        $this->assertInstanceOf(Driver\SQL\Unconnected::class, $driver);
    }

    public function testWhenUnconnectedObjectReturnsNullForDatabaseRequiredObjects()
    {
        $driver = new Driver\SQL\Unconnected();

        $this->assertNull($driver->getSeederRepository());
        $this->assertNull($driver->getMigrationRepository());
    }
}
