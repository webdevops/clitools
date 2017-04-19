<?php

namespace CliTools\Console\Command\Sync;

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
use CliTools\Shell\CommandBuilder\OutputCombineCommandBuilder;
use CliTools\Shell\CommandBuilder\CommandBuilder;

class DeployCommand extends AbstractRemoteSyncCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

        $this->confArea = 'deploy';

        $this->setName('deploy')
             ->setDescription('Deploy files and database to server');
    }

    /**
     * Startup task
     */
    protected function startup()
    {
        $this->output->writeln('<h2>Starting server deployment</h2>');
        parent::startup();
    }

    /**
     * Backup task
     */
    protected function runMain()
    {
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
            $this->output->writeln('<h1>Starting FILE deployment</h1>');
            $this->runTaskRsync();
        }

        // Sync database to local server
        if ($runMysql && $this->contextConfig->exists('mysql')) {
            $this->output->writeln('<h1>Starting MYSQL deployment</h1>');
            $this->runTaskDatabase();
        }
    }

    /**
     * Sync files with rsync
     */
    protected function runTaskRsync()
    {
        // ##################
        // Deploy dirs
        // ##################
        $source = $this->getRsyncWorkingPath();
        $target = $this->getRsyncPathFromConfig();

        $command = $this->createRsyncCommandWithConfiguration($source, $target, 'rsync');

        $command->executeInteractive();
    }

    /**
     * Sync database
     */
    protected function runTaskDatabase()
    {
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

            // ##############
            // Dump local DB
            // ##############
            $this->output->writeln('<p>Dumping local database "' . $localDatabase . '"</p>');

            $mysqldump = $this->createLocalMySqlDumpCommand($localDatabase);

            if ($this->contextConfig->exists('mysql.blacklist') ||
                $this->contextConfig->exists('mysql.whitelist') ||
                $this->contextConfig->exists('mysql.filter')) {
                $mysqldump = $this->addMysqlDumpFilterArguments(
                    $mysqldump,
                    $localDatabase,
                    false
                );
            }

            $command = new OutputCombineCommandBuilder();
            $command->addCommandForCombinedOutput($mysqldump);

            $command->setOutputRedirectToFile($dumpFile)
                    ->executeInteractive();

            ######################
            #Pushing DB to remote
            ######################
            $this->output->writeln('<p>Pushing "' . $dumpFile . '" into remote database "' . $foreignDatabase . '"</p>');

            switch ($this->contextConfig->get('mysql.compression')) {
                case 'bzip2':
                    $command = new CommandBuilder('bzcat', $dumpFile);
                    break;
                case 'gzip':
                    $command = new CommandBuilder('gzip', '-dc ' . $dumpFile);
                    break;
                default:
                    $command = new CommandBuilder('cat', $dumpFile);
            }

            $mysqlImportRemote = $this->createRemoteMySqlCommand($foreignDatabase);
            $mysqlImportRemote = $this->wrapRemoteCommand($mysqlImportRemote);
            $command->addPipeCommand($mysqlImportRemote);

            $command->executeInteractive();
        }
    }

}
