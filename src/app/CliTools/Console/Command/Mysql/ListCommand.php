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
use CliTools\Utility\FormatUtility;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('mysql:list')
            ->setDescription('List all databases')
            ->addOption(
                'sort-name', null,
                InputOption::VALUE_NONE,
                'Sort output by table count'
            )
            ->addOption(
                'sort-data',
                null,
                InputOption::VALUE_NONE,
                'Sort output by data size'
            )
            ->addOption(
                'sort-index',
                null,
                InputOption::VALUE_NONE,
                'Sort output by index size'
            )
            ->addOption(
                'sort-total',
                null,
                InputOption::VALUE_NONE,
                'Sort output by total size'
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

        // Get list of databases
        $query        = 'SELECT SCHEMA_NAME
                    FROM information_schema.SCHEMATA';
        $databaseList = DatabaseConnection::getCol($query);

        if (!empty($databaseList)) {

            // ########################
            // Fetch statistics
            // ########################

            $databaseRowList = array();
            foreach ($databaseList as $database) {
                // Skip internal mysql databases
                if (in_array(strtolower($database), array('mysql', 'information_schema', 'performance_schema'))) {
                    continue;
                }

                // Get all tables
                $query      = 'SELECT COUNT(*) AS count
                            FROM information_schema.tables
                           WHERE TABLE_SCHEMA = ' . DatabaseConnection::quote($database) . '
                             AND TABLE_TYPE = \'BASE TABLE\'';
                $tableCount = DatabaseConnection::getOne($query);

                // Get all views
                $query     = 'SELECT COUNT(*) AS count
                            FROM information_schema.tables
                           WHERE TABLE_SCHEMA = ' . DatabaseConnection::quote($database) . '
                             AND TABLE_TYPE LIKE \'%VIEW\'';
                $viewCount = DatabaseConnection::getOne($query);

                // Get size of database
                $query    = 'SELECT SUM(data_length) AS data_size,
                                 SUM(index_length) AS index_size,
                                 SUM(data_length + index_length) AS total_size
                            FROM information_schema.tables
                           WHERE TABLE_SCHEMA = ' . DatabaseConnection::quote($database);
                $statsRow = DatabaseConnection::getRow($query);


                $databaseRowList[$database] = array(
                    'name'        => $database,
                    'table_count' => $tableCount,
                    'view_count'  => $viewCount,
                    'data_size'   => $statsRow['data_size'],
                    'index_size'  => $statsRow['index_size'],
                    'total_size'  => $statsRow['total_size'],
                );
            }


            // ########################
            // Sorting
            // ########################

            // Sort: default by name (natural sort)
            uasort($databaseRowList, function ($a, $b) {
                return strnatcmp($a['name'], $b['name']);
            });

            // Sort: by table names
            if ($input->getOption('sort-name')) {
                uasort($databaseRowList, function ($a, $b) {
                    return $a['table_count'] < $b['table_count'];
                });
            }

            // Sort: by data size
            if ($input->getOption('sort-data')) {
                uasort($databaseRowList, function ($a, $b) {
                    return $a['data_size'] < $b['data_size'];
                });
            }

            // Sort: by index size
            if ($input->getOption('sort-index')) {
                uasort($databaseRowList, function ($a, $b) {
                    return $a['index_size'] < $b['index_size'];
                });
            }

            // Sort: by total size
            if ($input->getOption('sort-total')) {
                uasort($databaseRowList, function ($a, $b) {
                    return $a['total_size'] < $b['total_size'];
                });
            }

            // ########################
            // Stats
            // ########################

            $statsRow      = array(
                'name'        => '',
                'table_count' => 0,
                'view_count'  => 0,
                'data_size'   => 0,
                'index_size'  => 0,
                'total_size'  => 0,
            );
            $databaseCount = count($databaseRowList);

            foreach ($databaseRowList as $databaseRow) {
                $statsRow['table_count'] += $databaseRow['table_count'];
                $statsRow['view_count'] += $databaseRow['view_count'];

                $statsRow['data_size'] += $databaseRow['data_size'];
                $statsRow['index_size'] += $databaseRow['index_size'];
                $statsRow['total_size'] += $databaseRow['total_size'];
            }

            // ########################
            // Output
            // ########################

            /** @var \Symfony\Component\Console\Helper\Table $table */
            $table = new Table($output);
            $table->setHeaders(array('Database', 'Tables', 'Views', 'Data', 'Index', 'Total'));

            foreach ($databaseRowList as $databaseRow) {

                $databaseRow['table_count'] = FormatUtility::number($databaseRow['table_count']);
                $databaseRow['view_count']  = FormatUtility::number($databaseRow['view_count']);

                $databaseRow['data_size']  = FormatUtility::bytes($databaseRow['data_size']);
                $databaseRow['index_size'] = FormatUtility::bytes($databaseRow['index_size']);
                $databaseRow['total_size'] = FormatUtility::bytes($databaseRow['total_size']);

                $table->addRow(array_values($databaseRow));
            }

            // Stats: average
            if ($databaseCount >= 1) {
                $table->addRow(new TableSeparator());
                $statsAvgRow                = array();
                $statsAvgRow['name']        = 'Average';
                $statsAvgRow['table_count'] = FormatUtility::number($statsRow['table_count'] / $databaseCount);
                $statsAvgRow['view_count']  = FormatUtility::number($statsRow['view_count'] / $databaseCount);
                $statsAvgRow['data_size']   = FormatUtility::bytes($statsRow['data_size'] / $databaseCount);
                $statsAvgRow['index_size']  = FormatUtility::bytes($statsRow['index_size'] / $databaseCount);
                $statsAvgRow['total_size']  = FormatUtility::bytes($statsRow['total_size'] / $databaseCount);
                $table->addRow(array_values($statsAvgRow));
            }

            // Stats: total
            $statsTotalRow['name']        = 'Total';
            $statsTotalRow['table_count'] = FormatUtility::number($statsRow['table_count']);
            $statsTotalRow['view_count']  = FormatUtility::number($statsRow['view_count']);
            $statsTotalRow['data_size']   = FormatUtility::bytes($statsRow['data_size']);
            $statsTotalRow['index_size']  = FormatUtility::bytes($statsRow['index_size']);
            $statsTotalRow['total_size']  = FormatUtility::bytes($statsRow['total_size']);
            $table->addRow(array_values($statsTotalRow));

            $table->render();
        } else {
            $output->writeln('<comment>No databases found</comment>');
        }

        return 0;
    }
}
