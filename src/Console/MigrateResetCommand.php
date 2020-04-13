<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Console;

use Amp\Promise;
use Spacetab\Rdb\Generic\Migrator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Spacetab\Rdb\Driver;

/**
 * Class MigrateResetCommand
 *
 * @codeCoverageIgnore
 * @package Spacetab\Rdb\Console
 */
class MigrateResetCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('migrate:reset')
            ->setDescription('Rollback all database migrations')
            ->setHelp('Example of usage: rdb migrate:reset --connect "host=localhost user=root dbname=test"');

        $this
            ->addOption('connect', 'c', InputOption::VALUE_OPTIONAL, self::CONN_ARG_DESCRIPTION)
            ->addOption('database', 'd', InputOption::VALUE_OPTIONAL, self::DB_ARG_DESCRIPTION, Driver\DriverInterface::POSTGRES)
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, self::PATH_ARG_DESCRIPTION, Migrator::DEFAULT_MIGRATION_PATH);
    }

    /**
     * Execute command, captain.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     * @throws \Spacetab\Rdb\Exception\RdbException
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->getConsoleMigrator(
            $input, $output, fn(Migrator $migrator) => Promise\wait($migrator->reset())
        );
    }
}
