<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Console;

use Spacetab\Rdb\Generic\Migrator;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Spacetab\Rdb\Driver;
use Amp\Promise;

/**
 * Class MigrateStatusCommand
 *
 * @codeCoverageIgnore
 * @package Spacetab\Rdb\Console
 */
class MigrateStatusCommand extends Command
{
    protected static $defaultName = 'migrate:status';

    protected function configure()
    {
        $this
            ->setName('migrate:status')
            ->setDescription('Show the status of each migration')
            ->setHelp('Example of usage: rdb migrate:status --connect "host=localhost user=root dbname=test"');

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
        $items = [];
        $exit = $this->getConsoleMigrator($input, $output, function (Migrator $migrator) use (&$items) {
            $items = Promise\wait($migrator->status());
        });

        if (count($items) < 1) {
            return 1;
        }

        $this->makeTable($items);

        $table = new Table($output);
        $table->setHeaders(['Ran?', 'Migration', 'Batch']);
        $table->setRows($items);
        $table->render();

        return $exit;
    }

    /**
     * @param array $items
     * @return void
     */
    private function makeTable(array & $items): void
    {
        foreach ($items as &$item) {
            $item['status'] = $item['status'] ? '<info>Yes</info>' : '<fg=red>No</fg=red>';
        }

        unset($item);
    }
}
