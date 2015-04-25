<?php

namespace CliTools\Console\Command\System;

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
use CliTools\Utility\CommandExecutionUtility;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CliTools\Console\Builder\CommandBuilder;

class StartupCommand extends \CliTools\Console\Command\AbstractCommand implements \CliTools\Console\Filter\OnlyRootFilterInterface {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('system:startup')
            ->setDescription('System startup task');
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
        $this->setupBanner();
        $this->cleanupMysql();

        return 0;
    }

    /**
     * Setup banner
     */
    protected function setupBanner() {
        $output = '';
        CommandExecutionUtility::exec(CLITOOLS_COMMAND_CLI, $output, 'system:banner');

        $output = implode("\n", $output);

        // escape special chars for /etc/issue
        $outputIssue = addcslashes($output, '\\');

        file_put_contents('/etc/issue', $outputIssue);
        file_put_contents('/etc/motd', $output);
    }

    /**
     * Cleanup MySQL
     *
     * @return string
     */
    protected function cleanupMysql() {
        try {
            // ############################
            // Clear general log
            // ############################

            // Disable general log
            $query = 'SET GLOBAL general_log = \'OFF\'';
            DatabaseConnection::exec($query);

            // Fetch log file
            $query      = 'SHOW VARIABLES LIKE \'general_log_file\'';
            $logFileRow = DatabaseConnection::getRow($query);

            if (!empty($logFileRow['Value'])) {

                $command = new CommandBuilder('rm');
                $command->addArgument('-f')
                    ->addArgumentSeparator()
                    ->addArgument($logFileRow['Value'])
                    ->executeInteractive();
            }
        } catch (\Exception $e) {
            // do nothing if no mysql is running
        }
    }
}
