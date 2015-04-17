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
use CliTools\Utility\Typo3Utility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('typo3:cleanup')->setDescription('Cleanup caches, logs and indexed search')->addArgument('db',
                InputArgument::REQUIRED, 'Database name');
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
                             AND table_name = \'be_users\'';
                $isTypo3Database = DatabaseConnection::getOne($query);

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
    protected function cleanupTypo3Database($database) {
        $cleanupTableList = array();

        // Check if database is TYPO3 instance
        $query     = 'SELECT table_name
                    FROM information_schema.tables
                   WHERE table_schema = ' . DatabaseConnection::quote($database);
        $tableList = DatabaseConnection::getCol($query);

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

                case 'sys_dmain':
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

        $this->output->writeln('<info>Starting cleanup of database ' . $database . '...');

        DatabaseConnection::exec('USE `' . $database . '`');

        foreach ($cleanupTableList as $table) {
            $query = 'TRUNCATE `' . $table . '`';
            DatabaseConnection::exec($query);

            if ($this->output->isVerbose()) {
                $this->output->writeln('<comment>  -> Truncating table ' . $table . '</comment>');
            }
        }

        $this->output->writeln('<info>  -> finished</info>');
    }
}
