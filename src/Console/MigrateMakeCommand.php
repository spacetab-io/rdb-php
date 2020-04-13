<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Console;

use League\Flysystem\Adapter\Local;
use Spacetab\Rdb\Generic\Migrator;
use Spacetab\Rdb\Notifier\ConsoleNotifier;
use Spacetab\Rdb\Rdb;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Spacetab\Rdb\Driver;

/**
 * Class MigrateMakeCommand
 *
 * @codeCoverageIgnore
 * @package Spacetab\Rdb\Console
 */
class MigrateMakeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('make:migration')
            ->setDescription('Create a new migration files')
            ->setHelp('Example of usage: rdb make:migration create_users_table --create users');

        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the migration')
            ->addOption('database', 'd', InputOption::VALUE_OPTIONAL, self::DB_ARG_DESCRIPTION, Driver\DriverInterface::POSTGRES)
            ->addOption('create', null, InputOption::VALUE_OPTIONAL, 'The table to be created', false)
            ->addOption('table', null, InputOption::VALUE_OPTIONAL, 'The table to migrate')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, self::PATH_ARG_DESCRIPTION, Migrator::DEFAULT_MIGRATION_PATH);
    }

    /**
     * Execute command, captain.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     * @throws \Spacetab\Rdb\Exception\RdbException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name   = trim($input->getArgument('name'));
        $table  = $input->getOption('table');
        $create = $input->getOption('create') ?: false;

        // If no table was given as an option but a create option is given then we
        // will use the "create" option as the table name. This allows the developers
        // to pass a table name into this option as a short-cut for creating.
        if (! $table && is_string($create)) {
            $table = $create;

            $create = true;
        }

        $factory = new Driver\Factory();
        $driver = $factory->unconnected($this->getDatabaseType($input));

        $adapter = new Local($this->getMigratePath($input));

        $rdb = new Rdb($driver, new ConsoleNotifier($output));
        $rdb->getMigrator($adapter)->create($name, $table, $create);

        return 0;
    }
}
