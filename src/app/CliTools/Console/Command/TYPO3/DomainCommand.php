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
        $this->setName('typo3:domain')->setDescription('Add common development domains to database')->addArgument('db',
                InputArgument::OPTIONAL, 'Database name');
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

            // Get list of databases
            $query        = 'SELECT SCHEMA_NAME
                    FROM information_schema.SCHEMATA';
            $databaseList = DatabaseConnection::getCol($query);

            foreach ($databaseList as $dbName) {
                // Skip internal mysql databases
                if (in_array(strtolower($dbName), array('mysql', 'information_schema', 'performance_schema'))) {
                    continue;
                }

                // Check if database is TYPO3 instance
                $query           = 'SELECT COUNT(*) as count
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

        // ##################
        // Build domain
        // ##################
        $domain = null;

        if (preg_match('/^([^_]+)_([^_]+).*/i', $database, $matches)) {
            $domain = $matches[2] . '.' . $matches[1];
        } else {
            return false;
        }

        // ##################
        // Check if multi site
        // ##################
        $isMultiSite = false;

        $query            = 'SELECT uid
                    FROM ' . DatabaseConnection::sanitizeSqlDatabase($database) . '.pages
                   WHERE is_siteroot = 1
                     AND deleted = 0';
        $rootPageSiteList = DatabaseConnection::getCol($query);

        if (count($rootPageSiteList) >= 2) {
            $isMultiSite = true;
        }

        // ##################
        // Disable all other domains
        // ##################
        $query = 'UPDATE ' . DatabaseConnection::sanitizeSqlDatabase($database) . '.sys_domain
                     SET hidden = 1';
        DatabaseConnection::exec($query);


        // Get development domains from config
        $tldList = (array)$this->getApplication()->getConfigValue('config', 'domain_dev', array());

        foreach ($tldList as $tld) {
            $fullDomain = $domain . '.' . $tld;

            // ##############
            //  Loop through root pages
            // ##############
            foreach ($rootPageSiteList as $rootPageUid) {
                $rootPageDomain = $fullDomain;

                // Add rootpage id to domain if TYPO3 instance is multi page
                // eg. 123.dev.foobar.dev
                if ($isMultiSite) {
                    $rootPageDomain = $rootPageUid . '.' . $rootPageDomain;
                }

                // Check if we have already an entry
                $query       = 'SELECT uid
                            FROM ' . DatabaseConnection::sanitizeSqlDatabase($database) . '.sys_domain
                           WHERE pid = ' . (int)$rootPageUid . '
                             AND domainName = ' . DatabaseConnection::quote($rootPageDomain);
                $sysDomainId = DatabaseConnection::getOne($query);

                // Add/Update domain
                $query = 'INSERT INTO ' . DatabaseConnection::sanitizeSqlDatabase($database) . '.sys_domain
                                      (uid, pid, tstamp, crdate, cruser_id, hidden, domainName, sorting, forced)
                               VALUES (
                                   ' . (int)$sysDomainId . ',
                                   ' . (int)$rootPageUid . ',
                                   ' . time() . ',
                                   ' . time() . ',
                                   1,
                                   0,
                                   ' . DatabaseConnection::quote($rootPageDomain) . ',
                                   1,
                                   1
                               ) ON DUPLICATE KEY UPDATE
                                    pid        = VALUES(pid),
                                    hidden     = VALUES(hidden),
                                    domainName = VALUES(domainName),
                                    sorting    = VALUES(sorting),
                                    forced     = VALUES(forced)';
                DatabaseConnection::exec($query);

                if ($sysDomainId) {
                    $this->output->writeln('<comment>Domain "' . $rootPageDomain . '" updated to "' . $database . '"</comment>');
                } else {
                    $this->output->writeln('<info>Domain "' . $rootPageDomain . '" added to "' . $database . '"</info>');
                }
            }
        }
    }
}
