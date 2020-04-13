<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Notifier;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleNotifier implements NotifierInterface
{
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private OutputInterface $output;

    /**
     * ConsoleNotifier constructor.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;

        $this->output
            ->getFormatter()
            ->setStyle('cyan', new OutputFormatterStyle('cyan'));
    }

    /**
     * Notify user about actions.
     *
     * @param string $message
     * @return void
     */
    public function note(string $message): void
    {
        $this->output->writeln($message);
    }
}
