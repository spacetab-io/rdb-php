<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\DB\Postgres;

class MigrateStatusCommandTest extends DefaultTestCase
{
    public function testMigrateStatusCommand()
    {
        yield $this->clean();
        yield $this->migrateUpThroughConsole();
        yield $this->migrateStatusThroughConsole();
    }
}
