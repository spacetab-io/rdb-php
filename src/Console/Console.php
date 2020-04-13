<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Console
 * @codeCoverageIgnore
 * @package Spacetab\Rdb\Console
 */
final class Console
{
    public const VERSION = '1.0';
    public const WHOIS   = '<cyan>Rdb – great tool for working with database migrations and seeds (v' . self::VERSION . ').</cyan>';

    /**
     * Run console application.
     *
     * @param array $argv
     * @throws \Exception
     */
    public static function main($argv)
    {
        $output = self::cyanLine($argv);

        $app = new Application('');
        $app->add(new MigrateMakeCommand());
        $app->add(new MigrateInstallCommand());
        $app->add(new MigrateUpCommand());
        $app->add(new MigrateDownCommand());
        $app->add(new MigrateResetCommand());
        $app->add(new MigrateStatusCommand());
        $app->add(new MigrateRefreshCommand());
        $app->add(new SeedRunCommand());
        $app->add(new SeedMakeCommand());

        $app->run(null, $output);
    }

    /**
     * @param array $argv
     * @return \Symfony\Component\Console\Output\ConsoleOutput
     */
    private static function cyanLine($argv): ConsoleOutput
    {
        $output = new ConsoleOutput();
        $output->getFormatter()->setStyle('cyan', new OutputFormatterStyle('cyan'));

        if (self::isPrint($argv)) {
            $output->writeln(self::WHOIS);
            $output->writeln('');
        }

        return $output;
    }

    /**
     * @param array $argv
     * @return bool
     */
    private static function isPrint($argv): bool
    {
        return (isset($argv[1]) && in_array($argv[1], ['list', 'help'], true))
            || empty($argv[1]);
    }
}
