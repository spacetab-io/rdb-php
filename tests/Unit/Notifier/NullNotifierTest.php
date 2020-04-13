<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Notifier;

use Amp\PHPUnit\AsyncTestCase;
use Spacetab\Rdb\Notifier\NullNotifier;

class NullNotifierTest extends AsyncTestCase
{
    public function testNullNotifier()
    {
        $notifier = new NullNotifier();
        $notifier->note(__METHOD__);

        $this->assertEmpty($this->getActualOutputForAssertion());
    }
}
