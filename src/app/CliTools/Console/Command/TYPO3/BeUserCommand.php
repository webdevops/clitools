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
use CliTools\Utility\Typo3Utility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BeUserCommand extends \CliTools\Console\Command\Mysql\AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('typo3:beuser')
             ->setDescription('Add backend admin user to database')
             ->addArgument(
                 'database',
                 InputArgument::OPTIONAL,
                 'Database name'
             )
             ->addArgument(
                 'typo3-user',
                 InputArgument::OPTIONAL,
                 'Username'
             )
             ->addArgument(
                 'typo3-password',
                 InputArgument::OPTIONAL,
                 'Password'
             )
             ->addArgument(
                 'hash',
                 InputArgument::OPTIONAL,
                 'Choose the hashing algorithm for saving the password: md5, md5_salted, bcrypt, argon2i, argon2id'
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
        $dbName   = $input->getArgument('database');
        $username = $input->getArgument('typo3-user');
        $password = $input->getArgument('typo3-password');

        $output->writeln('<h2>Injecting TYPO3 backend user</h2>');

        // Set default user if not specified
        if (empty($username)) {
            $username = 'dev';
        }

        // check username
        if (!preg_match('/^[-_a-zA-Z0-9\.]+$/', $username)) {
            $output->writeln('<p-error>Invalid username</p-error>');

            return 1;
        }

        $output->writeln('<p>Using user: "' . htmlspecialchars($username) . '"</p>');

        // Set default password if not specified
        if (empty($password)) {
            $password = 'dev';
        }
        $output->writeln('<p>Using pass: "' . htmlspecialchars($password) . '"</p>');

        // ##################
        // Password hashing
        // ##################

        $hash = $input->getArgument('hash');
        list($password, $hash) = Typo3Utility::generatePassword($password, $hash);
        $this->output->writeln('<p>Generating password with "' . htmlspecialchars($hash) . '" hashing algorithm</p>');

        // ##############
        // Loop through databases
        // ##############

        if (empty($dbName)) {
            // ##############
            // All databases
            // ##############

            $databaseList = $this->mysqlDatabaseList();

            $dbFound = false;
            foreach ($databaseList as $dbName) {
                // Check if database is TYPO3 instance
                $query = 'SELECT COUNT(*) as count
                            FROM information_schema.tables
                           WHERE table_schema = ' . $this->mysqlQuote($dbName) . '
                             AND table_name = \'be_users\'';
                $isTypo3Database = $this->execSqlCommand($query);
                $isTypo3Database = reset($isTypo3Database);

                if ($isTypo3Database) {
                    $this->setTypo3UserForDatabase($dbName, $username, $password);
                    $dbFound = true;
                }
            }

            if (!$dbFound) {
                $output->writeln('<p-error>No valid TYPO3 database found</p-error>');
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
    protected function setTypo3UserForDatabase($database, $username, $password)
    {
        // ##################
        // Update/insert user
        // ##################

        // Default UserTS
        $tsConfig = array(
            // Backend stuff
            'options.clearCache.system = 1',
            'options.clearCache.all = 1',
            'options.enableShowPalettes = 1',
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

        // Default uc
        $uc = array(
            'thumbnailsByDefault' => 1,
            'recursiveDelete' => 1,
            'showHiddenFilesAndFolders' => 1,
            'edit_RTE' => 1,
            'resizeTextareas' => 1,
            'resizeTextareas_Flexible' => 1,
            'copyLevels' => 99,
            'rteResize' => 99,
            'moduleData' => array(
                'web_layout' => array(
                    // not "quick edit" but "columns" should be the
                    // default submodule within the page module
                    'function' => '1',
                ),
                'web_list' => array(
                    // check the important boxes right away
                    'bigControlPanel' => '1',
                    'clipBoard' => '1',
                    'localization' => '1',
                    'showPalettes' => '1',
                ),
                'web_ts' => array(
                    // not "constant editor" but "object browser"
                    'function' => 'TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController',
                    // better defaults for immediate debugging the actual typoscript
                    'ts_browser_type' => 'setup',
                    'ts_browser_const' => 'subst',
                    'ts_browser_fixedLgd' => '0',
                    'ts_browser_showComments' => '1',
                ),
                'file_list' => array(
                    'bigControlPanel' => '1',
                    'clipBoard' => '1',
                    'localization' => '1',
                    'showPalettes' => '1',
                ),
            ),
        );
        $uc = serialize($uc);

        try {
            // Get uid from current dev user (if already existing)
            $query = 'SELECT uid
                        FROM ' . DatabaseConnection::sanitizeSqlDatabase($database) . '.be_users
                       WHERE username = ' . $this->mysqlQuote($username) . '
                         AND deleted = 0';
            $beUserId = $this->execSqlCommand($query);
            $beUserId = reset($beUserId);

            // Insert or update user in TYPO3 database
            $query = 'INSERT INTO ' . DatabaseConnection::sanitizeSqlDatabase($database) . '.be_users
                                  (uid, tstamp, crdate, realName, username, password, TSconfig, uc, admin, disable, starttime, endtime)
                       VALUES(
                          ' . $this->mysqlQuote($beUserId) . ',
                          UNIX_TIMESTAMP(),
                          UNIX_TIMESTAMP(),
                          ' . $this->mysqlQuote('DEVELOPMENT') . ',
                          ' . $this->mysqlQuote($username) . ',
                          ' . $this->mysqlQuote($password) . ',
                          ' . $this->mysqlQuote($tsConfig) . ',
                          ' . $this->mysqlQuote($uc) . ',
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
            $this->execSqlCommand($query);

            if ($beUserId) {
                $this->output->writeln('<p>User successfully updated to "' . $database . '"</p>');
            } else {
                $this->output->writeln('<p>User successfully added to "' . $database . '"</p>');
            }
        } catch (\Exception $e) {
            $this->output->writeln('<p-error>User adding failed</p-error>');
        }
    }
}
