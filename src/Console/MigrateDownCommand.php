<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Console;

use Amp\Promise;
use Spacetab\Rdb\Driver;
use Spacetab\Rdb\Generic\Migrator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MigrateDownCommand
 *
 * @codeCoverageIgnore
 * @package Spacetab\Rdb\Console
 */
class MigrateDownCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('migrate:down')
            ->setAliases(['migrate:rollback'])
            ->setDescription('Rollback the last database migration')
            ->setHelp('Example of usage: rdb migrate:down --connect "host=localhost user=root dbname=test"');

        $this
            ->addOption('connect', 'c', InputOption::VALUE_OPTIONAL, self::CONN_ARG_DESCRIPTION)
            ->addOption('database', 'd', InputOption::VALUE_OPTIONAL, self::DB_ARG_DESCRIPTION, Driver\DriverInterface::POSTGRES)
            ->addOption('step', null, InputOption::VALUE_REQUIRED, self::STEP_ARG_DESCRIPTION)
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
        $options = [
            'step' => (int) $input->getOption('step')
        ];

        return $this->getConsoleMigrator(
            $input, $output, fn (Migrator $migrator) => Promise\wait($migrator->rollback($options))
        );
    }
}
