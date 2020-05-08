<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Console;

use Amp\Promise;
use Spacetab\Rdb\Driver;
use Spacetab\Rdb\Generic\Migrator;
use Spacetab\Rdb\Generic\Seeder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MigrateUpCommand
 *
 * @codeCoverageIgnore
 * @package Spacetab\Rdb\Console
 */
class MigrateUpCommand extends Command
{
    protected static $defaultName = 'migrate:up';

    protected function configure()
    {
        $this
            ->setName('migrate:up')
            ->setDescription('Run the database migrations')
            ->setHelp('Example of usage: rdb migrate:up --connect "host=localhost user=root db=test"');

        $this
            ->addOption('connect', 'c', InputOption::VALUE_REQUIRED, self::CONN_ARG_DESCRIPTION)
            ->addOption('database', 'd', InputOption::VALUE_REQUIRED, self::DB_ARG_DESCRIPTION, Driver\DriverInterface::POSTGRES)
            ->addOption('install', 'i', InputOption::VALUE_NONE, 'Create migration table if it does not exists.')
            ->addOption('step', null, InputOption::VALUE_NONE, self::STEP_ARG_DESCRIPTION)
            ->addOption('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.')
            ->addOption('seed-path', null, InputOption::VALUE_REQUIRED, self::PATH_ARG_DESCRIPTION, Seeder::DEFAULT_SEED_PATH)
            ->addOption('migrate-path', null, InputOption::VALUE_REQUIRED, self::PATH_ARG_DESCRIPTION, Migrator::DEFAULT_MIGRATION_PATH);
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
        $exitMigrator = $this->getConsoleMigrator($input, $output, function (Migrator $migrator, InputInterface $input) {
            $options = [];

            if ($input->getOption('install')) {
                Promise\wait($migrator->install());
            }

            if ($step = (bool) $input->getOption('step')) {
                $options = compact('step');
            }

            Promise\wait($migrator->migrate($options));
        }, false);

        $exitSeeder = 0;
        if ($input->getOption('seed')) {
            $exitSeeder = $this->getConsoleSeeder($input, $output, function (Seeder $seeder) {
                Promise\wait($seeder->run());
            });
        }

        return $exitMigrator + $exitSeeder;
    }

    protected function getMigratePath(InputInterface $input): string
    {
        return $input->getOption('migrate-path');
    }

    protected function getSeedPath(InputInterface $input): string
    {
        return $input->getOption('seed-path');
    }
}
