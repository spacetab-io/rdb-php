<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Integration\Postgres;

class MigrateDownCommandTest extends DefaultTestCase
{
    public function testMigrationDownCommandWithDefaultCase()
    {
        yield $this->clean();
        yield $this->migrateUpThroughConsole();
        yield $this->migrateDownThroughConsole();

        $migrations = yield $this->getTableContents('migrations');

        $this->assertEmpty($migrations);
    }

    public function testMigrationRollbackAlias()
    {
        yield $this->clean();
        yield $this->migrateUpThroughConsole();
        yield $this->migrateDownThroughConsole(null, 'rollback');

        $migrations = yield $this->getTableContents('migrations');

        $this->assertEmpty($migrations);
    }

    public function testMigrationDownCommandWithStepArgument()
    {
        yield $this->clean();
        yield $this->migrateUpThroughConsole();
        yield $this->migrateDownThroughConsole(1);

        $migrations = yield $this->getTableContents('migrations');

        $this->assertSame($migrations[0]['migration'], '2020_04_06_163414_create_test1_table');
        $this->assertSame($migrations[0]['batch'], 1);
    }
}
