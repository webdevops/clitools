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

class ConvertCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('mysql:convert')
            ->setDescription('Convert charset/collation of a database')
            ->addArgument(
                'database',
                InputArgument::REQUIRED,
                'Database name'
            )
             ->addOption(
                'charset',
                null,
                InputOption::VALUE_REQUIRED,
                'Charset (default: utf8)'
             )
            ->addOption(
                'collation',
                null,
                InputOption::VALUE_REQUIRED,
                'Collation (default: utf8_general_ci)'
            )
            ->addOption(
                'stdout',
                null,
                InputOption::VALUE_NONE,
                'Only print sql statements, do not execute it'
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
        $charset   = 'utf8';
        $collation = 'utf8_general_ci';
        $stdout    = false;

        $database = $input->getArgument('database');

        if ($input->getOption('charset')) {
            $charset = (string)$input->getOption('charset');
        }

        if ($input->getOption('collation')) {
            $collation = (string)$input->getOption('collation');
        }

        if ($input->getOption('stdout')) {
            $stdout = true;
        }

        // ##################
        // Alter database
        // ##################

        $query = 'ALTER DATABASE %s CHARACTER SET %s COLLATE %s';
        $query = sprintf($query,
            DatabaseConnection::sanitizeSqlDatabase($database),
            DatabaseConnection::quote($charset),
            DatabaseConnection::quote($collation)
        );

        if (!$stdout) {
            // Execute
            $output->writeln('<info>Converting database ' . $database . '</info>');
            DatabaseConnection::exec($query);
        } else {
            // Show only
            $output->writeln($query . ';');
        }

        // ##################
        // Alter tables
        // ##################
        $tableList = DatabaseConnection::tableList($database);

        foreach ($tableList as $table) {
            // Build statement
            $query = 'ALTER TABLE %s.%s CONVERT TO CHARACTER SET %s COLLATE %s';
            $query = sprintf($query,
                DatabaseConnection::sanitizeSqlDatabase($database),
                DatabaseConnection::sanitizeSqlTable($table),
                DatabaseConnection::quote($charset),
                DatabaseConnection::quote($collation)
            );

            if (!$stdout) {
                // Execute
                $output->writeln('<info>Converting table ' . $table . '</info>');
                DatabaseConnection::exec($query);
            } else {
                // Show only
                $output->writeln($query . ';');
            }
        }

        return 0;
    }
}
