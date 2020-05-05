<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Integration\Postgres;

use Amp\Process\Process;
use Amp\ByteStream;

class SeedMakeCommandTest extends DefaultTestCase
{
    private const MAKE_SEED_PATH = __DIR__ . '/../../Stub/Make/seeds';

    public function testSeedMakeCommand()
    {
        yield $this->clean();

        $process = new Process(['bin/rdb', 'make:seed', sprintf('test%d', rand(1, 99999)), '--path', self::MAKE_SEED_PATH]);
        yield $process->start();

        $contents = yield ByteStream\buffer($process->getStdout());

        $this->assertMatchesRegularExpression('/Seed .* created/', $contents);
    }
}
