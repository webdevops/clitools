<?php

namespace CliTools\Console\Command\Mysql;

/*
 * CliTools Command
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

use CliTools\Database\DatabaseConnection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SlowLogCommand extends AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('mysql:slowlog')
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
    public function execute(InputInterface $input, OutputInterface $output) {
        $this->elevateProcess($input, $output);

        $slowLogQueryTime = 1;
        $logNonIndexedQueries = false;

        // Slow log threshold
        if ($input->getOption('time')) {
            $slowLogQueryTime = $input->getOption('time');
        }

        // Also show not using indexes queries
        if ($input->getOption('no-index')) {
            $logNonIndexedQueries = true;
        }

        $debugLogLocation = $this->getApplication()->getConfigValue('db', 'debug_log_dir');
        $debugLogDir      = dirname($debugLogLocation);

        $output->writeln('<h2>Starting MySQL slow query log</h2>');

        // Create directory if not exists
        if (!is_dir($debugLogDir)) {
            if (!mkdir($debugLogDir, 0777, true)) {
                $output->writeln('<p-error>Could not create "' . $debugLogDir . '" directory</p-error>');
                throw new \CliTools\Exception\StopException(1);
            }
        }

        if (!empty($debugLogLocation)) {
            $debugLogLocation .= 'mysql_' . getmypid() . '.log';
            $query = 'SET GLOBAL slow_query_log_file = ' . DatabaseConnection::quote($debugLogLocation);
            DatabaseConnection::exec($query);
        }

        // Fetch log file
        $query      = 'SHOW VARIABLES LIKE \'slow_query_log_file\'';
        $logFileRow = DatabaseConnection::getRow($query);

        if (!empty($logFileRow['Value'])) {
            // Enable slow log
            $output->writeln('<p>Enabling slow log</p>');
            $query = 'SET GLOBAL slow_query_log = \'ON\'';
            DatabaseConnection::exec($query);

            // Enable slow log
            $output->writeln('<p>Set long_query_time to ' . (int)abs($slowLogQueryTime) . ' seconds</p>');
            $query = 'SET GLOBAL long_query_time = ' . (int)abs($slowLogQueryTime);
            DatabaseConnection::exec($query);

            // Enable log queries without indexes log
            if ($logNonIndexedQueries) {
                $output->writeln('<p>Enabling logging of queries without using indexes</p>');
                $query = 'SET GLOBAL log_queries_not_using_indexes = \'ON\'';
                DatabaseConnection::exec($query);
            } else {
                $output->writeln('<p>Disabling logging of queries without using indexes</p>');
                $query = 'SET GLOBAL log_queries_not_using_indexes = \'OFF\'';
                DatabaseConnection::exec($query);
            }

            // Setup teardown cleanup
            $tearDownFunc = function () use ($output, $logNonIndexedQueries) {
                // Disable general log
                $output->writeln('<p>Disable slow log</p>');
                $query = 'SET GLOBAL slow_query_log = \'OFF\'';
                DatabaseConnection::exec($query);

                if ($logNonIndexedQueries) {
                    // Disable log queries without indexes log
                    $query = 'SET GLOBAL log_queries_not_using_indexes = \'OFF\'';
                    DatabaseConnection::exec($query);
                }
            };
            $this->getApplication()->registerTearDown($tearDownFunc);

            // Read grep value
            $grep = null;
            if ($input->hasArgument('grep')) {
                $grep = $input->getArgument('grep');
            }

            // Tail logfile
            $logList = array(
                $logFileRow['Value'],
            );

            $optionList = array(
                '-n 0',
            );

            $this->showLog($logList, $input, $output, $grep, $optionList);

            return 0;
        } else {
            $output->writeln('<p-error>MySQL general_log_file not set</p-error>');

            return 1;
        }
    }
}
