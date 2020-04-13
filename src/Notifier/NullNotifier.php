<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Notifier;

class NullNotifier implements NotifierInterface
{
    /**
     * Notify user about actions.
     *
     * @param string $message
     * @return void
     */
    public function note(string $message): void
    {
        // to black hole...
    }
}
