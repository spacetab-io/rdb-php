<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Integration\Postgres;

class MigrateResetCommandTest extends DefaultTestCase
{
    public function testMigrateResetCommand()
    {
        yield $this->clean();
        yield $this->migrateUpThroughConsole();
        yield $this->migrateResetThroughConsole();

        $migrations = yield $this->getTableContents('migrations');
        $this->assertEmpty($migrations);
    }
}
