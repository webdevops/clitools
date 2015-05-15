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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('mysql:clear')
            ->setAliases(array('mysql:create'))
            ->setDescription('Clear (recreate) database')
            ->addArgument(
                'db',
                InputArgument::REQUIRED,
                'Database name'
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
        $database = $input->getArgument('db');

        if (DatabaseConnection::databaseExists($database)) {
            $output->writeln('<comment>Dropping Database "' . $database . '"...</comment>');
            $query = 'DROP DATABASE ' . DatabaseConnection::sanitizeSqlDatabase($database);
            DatabaseConnection::exec($query);
        }

        $output->writeln('<comment>Creating Database "' . $database . '"...</comment>');
        $query = 'CREATE DATABASE ' . DatabaseConnection::sanitizeSqlDatabase($database);
        DatabaseConnection::exec($query);

        $output->writeln('<info>Database "' . $database . '" dropped and recreated</info>');

        return 0;
    }
}
