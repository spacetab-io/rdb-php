<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Notifier;

use Amp\PHPUnit\AsyncTestCase;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Spacetab\Rdb\Notifier\LogNotifier;

class LogNotifierTest extends AsyncTestCase
{
    public function testLogNotification()
    {
        $log = new LogNotifier(new class extends NullLogger {
            public function log($level, $message, array $context = []) {
                echo $message;
            }
        }, LogLevel::DEBUG);

        $log->note(__METHOD__);

        $this->expectOutputString(__METHOD__);
    }
}
