<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Notifier;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogNotifier implements NotifierInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Logger level.
     *
     * @var string
     */
    private string $level;

    /**
     * LogNotifier constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param string $level
     */
    public function __construct(LoggerInterface $logger, string $level = LogLevel::INFO)
    {
        $this->logger = $logger;
        $this->level  = $level;
    }

    /**
     * Notify user about actions.
     *
     * @param string $message
     * @return void
     */
    public function note(string $message): void
    {
        $this->logger->log($this->level, strip_tags($message));
    }
}
