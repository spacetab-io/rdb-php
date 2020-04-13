<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Notifier;

use Amp\PHPUnit\AsyncTestCase;
use Spacetab\Rdb\Notifier\ConsoleNotifier;
use Symfony\Component\Console\Output\NullOutput;

class ConsoleNotifierTest extends AsyncTestCase
{
    public function testConsoleNotification()
    {
        $console = new ConsoleNotifier(new class extends NullOutput {
            public function writeln($messages, int $options = NullOutput::OUTPUT_NORMAL) {
                echo $messages;
            }
        });

        $console->note(__METHOD__);

        $this->expectOutputString(__METHOD__);
    }
}
