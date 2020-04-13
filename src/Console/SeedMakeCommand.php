<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Console;

use League\Flysystem\Adapter\Local;
use Spacetab\Rdb\Generic\Seeder;
use Spacetab\Rdb\Notifier\ConsoleNotifier;
use Spacetab\Rdb\Rdb;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Spacetab\Rdb\Driver;

/**
 * Class SeedMakeCommand
 *
 * @codeCoverageIgnore
 * @package Spacetab\Rdb\Console
 */
class SeedMakeCommand extends Command
{
    protected static $defaultName = 'make:seed';

    protected function configure()
    {
        $this
            ->setName('make:seed')
            ->setDescription('Create a new seeder file')
            ->setHelp('Example of usage: rdb make:seed user_seeder');

        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'The class name of the seeder', Seeder::DEFAULT_SEED_PATH)
            ->addOption('database', 'd', InputOption::VALUE_OPTIONAL, self::DB_ARG_DESCRIPTION, Driver\DriverInterface::POSTGRES)
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, self::PATH_ARG_DESCRIPTION, Seeder::DEFAULT_SEED_PATH);
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
        $name = $input->getArgument('name');

        $factory = new Driver\Factory();
        $driver = $factory->unconnected($this->getDatabaseType($input));

        $adapter = new Local($this->getSeedPath($input));

        $rdb = new Rdb($driver, new ConsoleNotifier($output));
        $rdb->getSeeder($adapter)->create($name);

        return 0;
    }
}
