<?php

namespace CliTools\Console\Command\Mysql;

/*
 * CliTools Command
 * Copyright (C) 2016 WebDevOps.io
 * Copyright (C) 2015 Markus Blaschke <markus@familie-blaschke.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use CliTools\Shell\CommandBuilder\DockerExecCommandBuilder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SlowLogCommand extends AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('mysql:slowlog')
             ->setDescription('Enable and show slow query log')
             ->addArgument(
                 'grep',
                 InputArgument::OPTIONAL,
                 'Grep'
             )
             ->addOption(
                 'time',
                 't',
                 InputOption::VALUE_REQUIRED,
                 'Slow query time (default 1 second)'
             )
             ->addOption(
                 'no-index',
                 'i',
                 InputOption::VALUE_NONE,
                 'Enable log queries without indexes log'
             )
            ->addOption(
                'keep-log',
                null,
                InputOption::VALUE_NONE,
                'Do not delete log after closing'
            );
    }

    /**
     * Execute command
     *
     * @param  InputInterface  $input  Input instance
     * @param  OutputInterface $output Output instance
     *
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $slowLogQueryTime     = 1;
        $logNonIndexedQueries = (bool)$input->getOption('no-index');
        $keepLog              = (bool)$input->getOption('keep-log');

        // Slow log threshold
        if ($input->getOption('time')) {
            $slowLogQueryTime = $input->getOption('time');
        }

        $debugLogLocation = $this->getApplication()
                                 ->getConfigValue('db', 'debug_log_dir', '/tmp');
        $debugLogDir      = dirname($debugLogLocation);

        $output->writeln('<h2>Starting MySQL slow query log</h2>');

        // Create directory if not exists
        if (!is_dir($debugLogDir)) {
            if (!mkdir($debugLogDir, 0777, true)) {
                $output->writeln('<p-error>Could not create "' . $debugLogDir . '" directory</p-error>');
                throw new \CliTools\Exception\StopException(1);
            }
        }

        $debugLogLocation .= 'mysql_' . getmypid() . '.log';
        $output->writeln('<p>Set general_log_file to ' . $debugLogLocation . '</p>');
        $query = 'SET GLOBAL slow_query_log_file = ' . $this->mysqlQuote($debugLogLocation);
        $this->execSqlCommand($query);

        // Enable slow log
        $output->writeln('<p>Enabling slow log</p>');
        $query = 'SET GLOBAL slow_query_log = \'ON\'';
        $this->execSqlCommand($query);

        // Enable slow log
        $output->writeln('<p>Set long_query_time to ' . (int)abs($slowLogQueryTime) . ' seconds</p>');
        $query = 'SET GLOBAL long_query_time = ' . (int)abs($slowLogQueryTime);
        $this->execSqlCommand($query);

        // Enable log queries without indexes log
        if ($logNonIndexedQueries) {
            $output->writeln('<p>Enabling logging of queries without using indexes</p>');
            $query = 'SET GLOBAL log_queries_not_using_indexes = \'ON\'';
            $this->execSqlCommand($query);
        } else {
            $output->writeln('<p>Disabling logging of queries without using indexes</p>');
            $query = 'SET GLOBAL log_queries_not_using_indexes = \'OFF\'';
            $this->execSqlCommand($query);
        }

        // Setup teardown cleanup
        $tearDownFunc = function () use ($output, $debugLogLocation, $logNonIndexedQueries, $keepLog) {
            // Disable general log
            $output->writeln('<p>Disable slow log</p>');
            $query = 'SET GLOBAL slow_query_log = \'OFF\'';
            $this->execSqlCommand($query);

            if ($logNonIndexedQueries) {
                // Disable log queries without indexes log
                $query = 'SET GLOBAL log_queries_not_using_indexes = \'OFF\'';
                $this->execSqlCommand($query);
            }

            if (!$keepLog) {
                $output->writeln('<p>Deleting logfile</p>');
                $command = $this->localDockerCommandBuilderFactory(AbstractCommand::DOCKER_ALIAS_MYSQL, 'rm', ['-f', $debugLogLocation]);
                $command->executeInteractive();
            } else {
                $output->writeln('<p>Keeping logfile</p>');
            }
        };
        $this->getApplication()
             ->registerTearDown($tearDownFunc);

        // Read grep value
        $grep = null;
        if ($input->hasArgument('grep')) {
            $grep = $input->getArgument('grep');
        }

        if ($this->getLocalDockerContainer(AbstractCommand::DOCKER_ALIAS_MYSQL)) {
            $command = new DockerExecCommandBuilder('tail', ['-f', $debugLogLocation]);
            $command->setDockerContainer($this->getLocalDockerContainer(AbstractCommand::DOCKER_ALIAS_MYSQL));
            $command->executeInteractive();
        } else {
            $this->showLog([$debugLogLocation], $input, $output, $grep, ['-n 0']);
        }

        return 0;
    }
}
