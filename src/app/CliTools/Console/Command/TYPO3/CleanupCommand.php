<?php

namespace CliTools\Console\Command\TYPO3;

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

use CliTools\Database\DatabaseConnection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupCommand extends \CliTools\Console\Command\Mysql\AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('typo3:cleanup')
             ->setDescription('Cleanup caches, logs and indexed search')
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
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // ##################
        // Init
        // ##################
        $dbName = $input->getArgument('db');

        $output->writeln('<h2>Cleanup TYPO3 database</h2>');

        // ##############
        // Loop through databases
        // ##############

        if (empty($dbName)) {
            // ##############
            // All databases
            // ##############

            // Get list of databases
            $databaseList = $this->mysqlDatabaseList();

            foreach ($databaseList as $dbName) {
                // Check if database is TYPO3 instance
                $query = 'SELECT COUNT(*) as count
                            FROM information_schema.tables
                           WHERE table_schema = ' . $this->mysqlQuote($dbName) . '
                             AND table_name = \'be_users\'';
                $isTypo3Database = $this->execSqlCommand($query);
                $isTypo3Database = reset($isTypo3Database);

                if ($isTypo3Database) {
                    $this->cleanupTypo3Database($dbName);
                }
            }
        } else {
            // ##############
            // One databases
            // ##############
            $this->cleanupTypo3Database($dbName);
        }

        return 0;
    }

    /**
     * Cleanup TYPO3 database
     *
     * @param string $database Database
     */
    protected function cleanupTypo3Database($database)
    {
        $cleanupTableList = array();

        // Check if database is TYPO3 instance
        $tableList = $this->mysqlTableList($database);

        foreach ($tableList as $table) {
            $clearTable = false;

            // Caching und indexing tables
            switch (true) {
                case (strpos($table, 'cache_') === 0):
                case (strpos($table, 'cachingframework_') === 0):
                case (strpos($table, 'cf_') === 0):
                    // Caching framework
                    $clearTable = true;
                    break;

                case (strpos($table, 'index_') === 0):
                    // EXT:indexed_search
                    $clearTable = true;
                    break;
            }


            switch ($table) {
                case 'sys_history':
                case 'sys_log':
                    // History/Log
                    $clearTable = true;
                    break;

                case 'sys_domain':
                    // EXT:direct_mail
                    $clearTable = true;
                    break;

                case 'tx_devlog':
                    // EXT:devlog
                    $clearTable = true;
                    break;

                case 'tx_realurl_errorlog':
                case 'tx_realurl_pathcache':
                case 'tx_realurl_urldecodecache':
                case 'tx_realurl_urlencodecache':
                    // EXT: realurl
                    $clearTable = true;
                    break;

                case 'tx_solr_cache':
                case 'tx_solr_cache_tags':
                    // EXT:solr
                    $clearTable = true;
                    break;
            }

            if ($clearTable) {
                $cleanupTableList[] = $table;
            }
        }

        $this->output->writeln('<p>Starting cleanup of database "' . $database . '"</p>');

        foreach ($cleanupTableList as $table) {
            $query = 'TRUNCATE ' . DatabaseConnection::sanitizeSqlDatabase($database) . '.' . DatabaseConnection::sanitizeSqlTable($table);
            $this->execSqlCommand($query);

            if ($this->output->isVerbose()) {
                $this->output->writeln('<p>Truncating table ' . $table . '</p>');
            }
        }

        $this->output->writeln('<p>finished</p>');
    }
}
