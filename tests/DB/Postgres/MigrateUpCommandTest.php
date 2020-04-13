<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\DB\Postgres;

class MigrateUpCommandTest extends DefaultTestCase
{
    public function testMigrationUpCommandWithDefaultCase()
    {
        yield $this->clean();

        yield $this->migrateUpThroughConsole();
        $migrations = yield $this->getTableContents('migrations');

        $this->assertSame($migrations[0]['migration'], '2020_04_06_163414_create_test1_table');
        $this->assertSame($migrations[0]['batch'], 1);
    }

    public function testMigrationUpCommandWithSeedOption()
    {
        yield $this->clean();

        $contents = yield $this->migrateUpThroughConsole(true);
        $this->assertMatchesRegularExpression('/Seed .*/', $contents);

        $migrations = yield $this->getTableContents('migrations');

        $this->assertSame($migrations[0]['migration'], '2020_04_06_163414_create_test1_table');
        $this->assertSame($migrations[0]['batch'], 1);

        $this->assertSame($migrations[1]['migration'], '2020_04_06_163460_create_test2_table');
        $this->assertSame($migrations[1]['batch'], 1);

        $test1 = yield $this->getTableContents('test1');

        $this->assertSame($test1[0]['text'], 'test');
    }

    public function testMigrationUpCommandWithStepOption()
    {
        yield $this->clean();
        yield $this->migrateUpThroughConsole(false, true);

        $migrations = yield $this->getTableContents('migrations');

        $this->assertSame($migrations[0]['migration'], '2020_04_06_163414_create_test1_table');
        $this->assertSame($migrations[0]['batch'], 1);

        $this->assertSame($migrations[1]['migration'], '2020_04_06_163460_create_test2_table');
        $this->assertSame($migrations[1]['batch'], 2);
    }
}
