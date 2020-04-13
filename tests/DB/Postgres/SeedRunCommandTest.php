<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\DB\Postgres;

use Amp\Process\Process;
use Amp\ByteStream;

class SeedRunCommandTest extends DefaultTestCase
{
    public function testSeedRunCommandWithoutName()
    {
        yield $this->clean();
        yield $this->migrateUpThroughConsole();

        $process = new Process(['bin/rdb', 'seed:run', '--connect', $this->getConnectionString(), '--path', self::SEEDS_PATH]);
        yield $process->start();

        $contents = yield ByteStream\buffer($process->getStdout());

        $this->assertMatchesRegularExpression('/Seed .* executed/', $contents);
    }
}
