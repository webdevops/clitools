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

use CliTools\Shell\CommandBuilder\OutputCombineCommandBuilder;

class BackupCommand extends AbstractShareCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('sync:backup')
             ->setDescription('Backup files and database from share');
    }

    /**
     * Startup task
     */
    protected function startup()
    {
        $this->output->writeln('<h2>Starting share backup</h2>');
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
        // Backup dirs
        // ##################
        if ($runRsync && $this->contextConfig->exists('rsync.directory')) {
            $this->runTaskRsync();
        }

        // ##################
        // Backup databases
        // ##################
        if ($runMysql && $this->contextConfig->exists('mysql.database')) {
            $this->runTaskMysql();
        }
    }

    /**
     * Sync files with rsync
     */
    protected function runTaskRsync()
    {
        $source = $this->getRsyncWorkingPath();
        $target = $this->getRsyncPathFromConfig() . self::PATH_DATA;

        $command = $this->createRsyncCommandWithConfiguration($source, $target, 'rsync');
        $command->executeInteractive();
    }

    /**
     * Sync database
     */
    protected function runTaskMysql()
    {
        // ##################
        // Sync databases
        // ##################
        foreach ($this->contextConfig->getArray('mysql.database') as $database) {
            // make sure we don't have any leading whitespaces
            $database = trim($database);

            // dump database
            $dumpFile = $this->tempDir . '/mysql/' . $database . '.dump';

            // ##########
            // Dump from server
            // ##########
            $this->output->writeln('<p>Dumping database "' . $database . '"</p>');

            $mysqldump = $this->createLocalMySqlDumpCommand($database);

            if ($this->contextConfig->exists('mysql.filter')) {
                $mysqldump = $this->addMysqlDumpFilterArguments($mysqldump, $database, false);
            }

            $command = new OutputCombineCommandBuilder();
            $command->addCommandForCombinedOutput($mysqldump);

            $command->setOutputRedirectToFile($dumpFile)
                    ->executeInteractive();
        }

        // ##################
        // Backup mysql dump
        // ##################
        $source  = $this->tempDir;
        $target  = $this->getRsyncPathFromConfig() . self::PATH_DUMP;
        $command = $this->createRsyncCommand($source, $target);
        $command->executeInteractive();
    }
}
