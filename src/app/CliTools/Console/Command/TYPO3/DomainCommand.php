<?php

namespace CliTools\Console\Command\TYPO3;

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

class DomainCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('typo3:domain')
            ->setDescription('Add common development domains to database')
            ->addArgument(
                'db',
                InputArgument::OPTIONAL,
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
        // ##################
        // Init
        // ##################
        $dbName = $input->getArgument('db');

        // ##############
        // Loop through databases
        // ##############

        if (empty($dbName)) {
            // ##############
            // All databases
            // ##############
            $databaseList = DatabaseConnection::databaseList();

            foreach ($databaseList as $dbName) {
                // Check if database is TYPO3 instance
                $query = 'SELECT COUNT(*) as count
                            FROM information_schema.tables
                           WHERE table_schema = ' . DatabaseConnection::quote($dbName) . '
                             AND table_name = \'sys_domain\'';
                $isTypo3Database = DatabaseConnection::getOne($query);

                if ($isTypo3Database) {
                    $this->setupDevelopmentDomainsForDatabase($dbName);
                }
            }
        } else {
            // ##############
            // One databases
            // ##############
            $this->setupDevelopmentDomainsForDatabase($dbName);
        }

        return 0;
    }

    /**
     * Set development domains for TYPO3 database
     *
     * @param  string $database Database
     *
     * @return void
     */
    protected function setupDevelopmentDomainsForDatabase($database) {

        $domain = '.' . $this->getApplication()->getConfigValue('config', 'domain_dev');
        $domainLength = strlen($domain);

        if (DatabaseConnection::tableExists($database, 'sys_domain')) {
            // ##################
            // Fix domains
            // ##################
            $query = 'UPDATE ' . DatabaseConnection::sanitizeSqlDatabase($database) . '.sys_domain
                         SET domainName = CONCAT(domainName, ' . DatabaseConnection::quote($domain) . ')
                       WHERE RIGHT(domainName, ' . $domainLength . ') <> ' . DatabaseConnection::quote($domain);
            DatabaseConnection::exec($query);

            // ##################
            // Show all domains
            // ##################
            $query = 'SELECT domainName
                        FROM ' . DatabaseConnection::sanitizeSqlDatabase($database) . '.sys_domain
                       WHERE hidden = 0';
            $domainList = DatabaseConnection::getCol($query);

            $this->output->writeln('<info>Domain list of "' . $database . '":</info>');

            foreach ($domainList as $domain) {
                $this->output->writeln('    <info>' . $domain . '</info>');
            }
            $this->output->writeln('');
        } else {
            $this->output->writeln('<info>Table "sys_domain" doesn\'t exists in database "' . $database . '"');
        }
    }
}
