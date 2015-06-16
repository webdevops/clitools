<?php

namespace CliTools\Console\Command\Sync;

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

class ServerCommand extends AbstractRemoteSyncCommand {

    /**
     * Configure command
     */
    protected function configure() {
        parent::configure();

        $this->confArea = 'sync';

        $this
            ->setName('sync:server')
            ->setDescription('Sync files and database from server');
    }

    /**
     * Startup task
     */
    protected function startup() {
        $this->output->writeln('<h2>Starting server synchronization</h2>');
        parent::startup();
    }

    /**
     * Validate configuration
     *
     * @return boolean
     */
    protected function validateConfiguration() {
        $ret = parent::validateConfiguration();

        $output = $this->output;

        // ##################
        // SSH (optional)
        // ##################

        if ($this->contextConfig->exists('ssh')) {
            // Check if one database is configured
            if (!$this->contextConfig->exists('ssh.hostname')) {
                $output->writeln('<p-error>No ssh hostname configuration found</p-error>');
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     * Backup task
     */
    protected function runMain() {
        // ##################
        // Option specific runners
        // ##################
        $runRsync = true;
        $runMysql = true;

        if ($this->input->getOption('mysql') || $this->input->getOption('rsync')) {
            // don't run rsync if not specifiecd
            $runRsync = $this->input->getOption('rsync');

            // don't run mysql if not specifiecd
            $runMysql = $this->input->getOption('mysql');
        }

        // ##################
        // Run tasks
        // ##################

        // Check database connection
        if ($runMysql && $this->contextConfig->exists('mysql')) {
            DatabaseConnection::ping();
        }

        // Sync files with rsync to local storage
        if ($runRsync && $this->contextConfig->exists('rsync')) {
            $this->output->writeln('<h1>Starting FILE sync</h1>');
            $this->runTaskRsync();
        }

        // Sync database to local server
        if ($runMysql && $this->contextConfig->exists('mysql')) {
            $this->output->writeln('<h1>Starting MYSQL sync</h1>');
            $this->runTaskDatabase();
        }
    }

    /**
     * Sync files with rsync
     */
    protected function runTaskRsync() {
        // ##################
        // Restore dirs
        // ##################
        $source = $this->getRsyncPathFromConfig();
        $target = $this->getRsyncWorkingPath();

        $command = $this->createRsyncCommandWithConfiguration($source, $target, 'rsync');
        $command->executeInteractive();
    }

    /**
     * Sync database
     */
    protected function runTaskDatabase() {
        // ##################
        // Sync databases
        // ##################
        foreach ($this->contextConfig->getArray('mysql.database') as $databaseConf) {
            if (strpos($databaseConf, ':') !== false) {
                // local and foreign database in one string
                list($localDatabase, $foreignDatabase) = explode(':', $databaseConf, 2);
            } else {
                // database equal
                $localDatabase   = $databaseConf;
                $foreignDatabase = $databaseConf;
            }

            // make sure we don't have any leading whitespaces
            $localDatabase   = trim($localDatabase);
            $foreignDatabase = trim($foreignDatabase);

            $dumpFile = $this->tempDir . '/' . $localDatabase . '.sql.dump';

            // ##########
            // Dump from server
            // ##########
            $this->output->writeln('<p>Fetching foreign database "' . $foreignDatabase . '"</p>');

            $mysqldump = $this->createRemoteMySqlDumpCommand($foreignDatabase);

            if ($this->contextConfig->exists('mysql.filter')) {
                $mysqldump = $this->addMysqlDumpFilterArguments($mysqldump, $foreignDatabase, $this->contextConfig->get('mysql.filter'));
            }

            $command = $this->wrapRemoteCommand($mysqldump);
            $command->setOutputRedirectToFile($dumpFile);

            $command->executeInteractive();

            // ##########
            // Restore local
            // ##########
            $this->output->writeln('<p>Restoring database "' . $localDatabase . '"</p>');

            $this->createMysqlRestoreCommand($localDatabase, $dumpFile)->executeInteractive();
        }
    }


}
