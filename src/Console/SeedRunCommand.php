<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Console;

use Amp\Promise;
use Spacetab\Rdb\Generic\Seeder;
use Spacetab\Rdb\Driver;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SeedRunCommand
 *
 * @codeCoverageIgnore
 * @package Spacetab\Rdb\Console
 */
class SeedRunCommand extends Command
{
    protected static $defaultName = 'seed:run';

    protected function configure()
    {
        $this
            ->setName('seed:run')
            ->setDescription('Seed the database with records')
            ->setHelp('Example of usage: rdb seed:run --connect "host=localhost user=root dbname=test"');

        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'The class name of the root seeder')
            ->addOption('connect', 'c', InputOption::VALUE_OPTIONAL, self::CONN_ARG_DESCRIPTION)
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
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        return $this->getConsoleSeeder($input, $output, function (Seeder $seeder) use ($name, $output) {
            Promise\wait($seeder->run($name));

            $output->writeln('');
            $output->writeln('<comment>Seed completed</comment>');
        });
    }
}
