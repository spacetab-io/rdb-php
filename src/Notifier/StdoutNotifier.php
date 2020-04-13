<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Notifier;

/**
 * Class StdoutNotifier
 *
 * @codeCoverageIgnore
 * @package Spacetab\Rdb\Notifier
 */
class StdoutNotifier implements NotifierInterface
{
    /**
     * Notify user about actions.
     *
     * @param string $message
     * @return void
     */
    public function note(string $message): void
    {
        fwrite(STDOUT, strip_tags($message) . PHP_EOL);
    }
}
