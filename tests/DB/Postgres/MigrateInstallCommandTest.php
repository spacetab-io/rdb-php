<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\DB\Postgres;

use Amp\Process\Process;
use Amp\ByteStream;

class MigrateInstallCommandTest extends DefaultTestCase
{
    public function testInstallationWithDefaultArguments()
    {
        yield $this->clean();

        $process = new Process(['bin/rdb', 'migrate:install', '--connect', $this->getConnectionString()]);
        yield $process->start();

        $contents = yield ByteStream\buffer($process->getStdout());

        $this->assertMatchesRegularExpression('/Migration table created successfully/', $contents);
    }
}
