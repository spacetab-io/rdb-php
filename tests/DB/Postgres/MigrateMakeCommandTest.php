<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\DB\Postgres;

use Amp\Process\Process;
use Amp\ByteStream;

class MigrateMakeCommandTest extends DefaultTestCase
{
    private const MAKE_MIGRATION_PATH = __DIR__ . '/../../Stub/Make/migrations';

    public function testMakeMigrationFileWithDefaultCase()
    {
        yield $this->clean();

        $process = new Process(['bin/rdb', 'make:migration', 'create_test3_table', '--path', self::MAKE_MIGRATION_PATH]);
        yield $process->start();

        $contents = yield ByteStream\buffer($process->getStdout());

        $this->assertMatchesRegularExpression('/Migration .* created/', $contents);
    }
}
