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

class BeUserCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('typo3:beuser')
            ->setDescription('Add backend admin user to database')
            ->addArgument(
                'database',
                InputArgument::OPTIONAL,
                'Database name'
            )
            ->addArgument(
                'user',
                InputArgument::OPTIONAL,
                'Username'
            )
            ->addArgument(
                'password',
                InputArgument::OPTIONAL,
                'Password'
            )
            ->addOption(
                'plain',
                null,
                InputOption::VALUE_NONE,
                'Do not crypt password (non salted password)'
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
        $dbName   = $input->getArgument('database');
        $username = $input->getArgument('user');
        $password = $input->getArgument('password');

        // Set default user if not specified
        if (empty($username)) {
            $username = 'dev';
        }

        // check username
        if (!preg_match('/^[-_a-zA-Z0-9\.]+$/', $username)) {
            $output->writeln('<error>Invalid username</error>');

            return 1;
        }

        $output->writeln('<comment>Using user: "' . htmlspecialchars($username) . '"</comment>');

        // Set default password if not specified
        if (empty($password)) {
            $password = 'dev';
        }
        $output->writeln('<comment>Using pass: "' . htmlspecialchars($password) . '"</comment>');

        // ##################
        // Salting
        // ##################

        if ($input->getOption('plain')) {
            // Standard md5
            $password = Typo3Utility::generatePassword($password, Typo3Utility::PASSWORD_TYPE_MD5);
            $this->output->writeln('<comment>Generating plain (non salted) md5 password</comment>');
        } else {
            // Salted md5
            $password = Typo3Utility::generatePassword($password, Typo3Utility::PASSWORD_TYPE_MD5_SALTED);
            $this->output->writeln('<comment>Generating salted md5 password</comment>');
        }

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

            $dbFound = false;
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
                    $this->setTypo3UserForDatabase($dbName, $username, $password);
                    $dbFound = true;
                }
            }

            if (!$dbFound) {
                $output->writeln('<error>No valid TYPO3 database found</error>');
            }
        } else {
            // ##############
            // One databases
            // ##############
            $this->setTypo3UserForDatabase($dbName, $username, $password);
        }

        return 0;
    }

    /**
     * Set TYPO3 user for database
     *
     * @param string $database Database
     * @param string $username Username
     * @param string $password Password (salted/hashed)
     */
    protected function setTypo3UserForDatabase($database, $username, $password) {
        // ##################
        // Update/insert user
        // ##################

        // Default UserTS
        $tsConfig = array(
            // Backend stuff
            'options.clearCache.system = 1',
            'options.clearCache.all = 1',
            'options.enableShowPalettes = 1',
            'options.alertPopups = 254',
            'options.pageTree.showPageIdWithTitle = 1',
            'options.pageTree.showPathAboveMounts = 1',
            'options.pageTree.showDomainNameWithTitle = 1',
            // adm panel
            'admPanel.enable.edit = 1',
            'admPanel.module.edit.forceDisplayFieldIcons = 1',
            'admPanel.hide = 0',
            // Setup defaults
            'setup.default.thumbnailsByDefault = 1',
            'setup.default.enableFlashUploader = 0',
            'setup.default.recursiveDelete = 1',
            'setup.default.showHiddenFilesAndFolders = 1',
            'setup.default.resizeTextareas_Flexible = 1',
            'setup.default.copyLevels = 99',
            'setup.default.rteResize = 99',
            // Web list
            'setup.default.moduleData.web_list.bigControlPanel = 1',
            'setup.default.moduleData.web_list.clipBoard = 1',
            'setup.default.moduleData.web_list.localization = 1',
            'setup.default.moduleData.web_list.showPalettes = 1',
            // File list
            'setup.default.moduleData.file_list.bigControlPanel = 1',
            'setup.default.moduleData.file_list.clipBoard = 1',
            'setup.default.moduleData.file_list.localization = 1',
            'setup.default.moduleData.file_list.showPalettes = 1',

        );
        $tsConfig = implode("\n", $tsConfig);

        try {
            // Get uid from current dev user (if already existing)
            $query    = 'SELECT uid
                        FROM `' . DatabaseConnection::sanitizeSqlDatabase($database) . '`.be_users
                       WHERE username = ' . DatabaseConnection::quote($username) . '
                         AND deleted = 0';
            $beUserId = DatabaseConnection::getOne($query);

            // Insert or update user in TYPO3 database
            $query = 'INSERT INTO `' . DatabaseConnection::sanitizeSqlDatabase($database) . '`.be_users
                                  (uid, tstamp, crdate, realName, username, password, TSconfig, admin, disable, starttime, endtime)
                       VALUES(
                          ' . DatabaseConnection::quote($beUserId) . ',
                          UNIX_TIMESTAMP(),
                          UNIX_TIMESTAMP(),
                          ' . DatabaseConnection::quote('DEVELOPMENT') . ',
                          ' . DatabaseConnection::quote($username) . ',
                          ' . DatabaseConnection::quote($password) . ',
                          ' . DatabaseConnection::quote($tsConfig) . ',
                          1,
                          0,
                          0,
                          0
                       ) ON DUPLICATE KEY UPDATE
                            realName  = VALUES(realName),
                            password  = VALUES(password),
                            TSconfig  = VALUES(TSconfig),
                            disable   = VALUES(disable),
                            starttime = VALUES(starttime),
                            endtime   = VALUES(endtime)';
            DatabaseConnection::exec($query);

            if ($beUserId) {
                $this->output->writeln('<comment>User successfully updated to "' . $database . '"</comment>');
            } else {
                $this->output->writeln('<info>User successfully added to "' . $database . '"</info>');
            }
        } catch (\Exception $e) {
            $this->output->writeln('<error>User adding failed</error>');
        }
    }
}
