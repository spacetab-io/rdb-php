<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Integration\Postgres;

class MigrateStatusCommandTest extends DefaultTestCase
{
    public function testMigrateStatusCommand()
    {
        yield $this->clean();
        yield $this->migrateUpThroughConsole();
        yield $this->migrateStatusThroughConsole();
    }
}
