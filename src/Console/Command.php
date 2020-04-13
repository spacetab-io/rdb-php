<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Console;

use Amp\Delayed;
use Amp\Promise;
use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use Spacetab\Rdb\Notifier\ConsoleNotifier;
use Spacetab\Rdb\Rdb;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Spacetab\Rdb\Driver;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Command
 *
 * @codeCoverageIgnore
 * @package Spacetab\Rdb\Console
 */
abstract class Command extends SymfonyCommand
{
    protected const DB_ARG_DESCRIPTION   = 'Type of database.';
    protected const CONN_ARG_DESCRIPTION = 'Connection string for connect to database.';
    protected const STEP_ARG_DESCRIPTION = 'Force the migrations to be run so they can be rolled back individually.';
    protected const PATH_ARG_DESCRIPTION = 'The path to the migrations files to use.';

    /**
     * @inheritDoc
     */
    protected function getConnectionString(InputInterface $input): string
    {
        $connect = $input->getOption('connect');

        if (is_null($connect)) {
            $message = 'Connection string must be provided if [getConnectionString] method not overload.';
            $message .= ' Use --connect flag to fix this issue.';

            throw new InvalidArgumentException($message);
        }

        return $input->getOption('connect');
    }

    protected function getDatabaseType(InputInterface $input): string
    {
        return $input->getOption('database');
    }

    protected function getMigratePath(InputInterface $input): string
    {
        return $input->getOption('path');
    }

    protected function getSeedPath(InputInterface $input): string
    {
        return $input->getOption('path');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param callable $callback
     * @param bool $checkTable
     * @return int
     * @throws \Spacetab\Rdb\Exception\RdbException
     * @throws \Throwable
     */
    protected function getConsoleMigrator(InputInterface $input, OutputInterface $output, callable $callback, bool $checkTable = true): int
    {
        $factory = new Driver\Factory();
        $driver = $factory->connect($this->getDatabaseType($input), $this->getConnectionString($input));

        $adapter = new Local($this->getMigratePath($input));
        $rdb = new Rdb($driver, new ConsoleNotifier($output));

        $migrator = $rdb->getMigrator($adapter);

        try {
            if ($checkTable && !Promise\wait($migrator->exists())) {
                $output->writeln('<info>Migration table not found.</info>');
                return 1;
            }

            $callback($migrator, $input, $output);
        } finally {
            $factory->close();
        }

        return 0;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param callable $callback
     * @return int
     * @throws \Spacetab\Rdb\Exception\RdbException
     * @throws \Throwable
     */
    protected function getConsoleSeeder(InputInterface $input, OutputInterface $output, callable $callback): int
    {
        $factory = new Driver\Factory();
        $driver = $factory->connect($this->getDatabaseType($input), $this->getConnectionString($input));

        $adapter = new Local($this->getSeedPath($input));
        $rdb = new Rdb($driver, new ConsoleNotifier($output));

        $seeder = $rdb->getSeeder($adapter);

        try {
            $callback($seeder, $input, $output);
        } finally {
            $factory->close();
        }

        return 0;
    }
}
