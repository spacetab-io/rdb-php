<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Notifier;

interface NotifierInterface
{
    public const LOG_CHANNEL = 'Rdb';

    /**
     * Notify user about actions.
     *
     * @param string $message
     * @return void
     */
    public function note(string $message): void;
}
