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

use CliTools\Console\Builder\CommandBuilder;
use CliTools\Console\Builder\SelfCommandBuilder;
use CliTools\Console\Builder\CommandBuilderInterface;

class BackupCommand extends \CliTools\Console\Command\Sync\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('sync:sync')
             ->setDescription('Sync from live server');
    }

    /**
     * Backup task
     */
    protected function runTask() {
        // Sync files with rsync to local storage
        if (!empty($this->config->sync['rsync'])) {
            $this->runTaskRsync();
        }

        // Sync database to local server
        if (!empty($this->config->sync['mysql']) && !empty($this->config->sync['mysql']['database'])) {
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
        $source = $this->config->sync['rsync']['source'];
        $target = $this->workingPath;
        $command = $this->createSyncRsyncCommand($source, $target, true);

        $command->executeInteractive();
    }

    /**
     * Sync database
     */
    protected function runTaskDatabase() {
        // ##################
        // Sync databases
        // ##################
        $mysqlConf = $this->config->sync['mysql'];

        $mysqlDumpTemplate = new CommandBuilder('mysqldump');
        $mysqlDumpTemplate->addArgumentTemplate('-u%s', $mysqlConf['username'])
                          ->addArgumentTemplate('-p%s', $mysqlConf['password'])
                          ->addArgumentTemplate('-h%s', $mysqlConf['host'])
                          ->addPipeCommand( new CommandBuilder('bzip2', '--compress --stdout') );

        $sshConnectionTemplate = new CommandBuilder('ssh');
        $sshConnectionTemplate->addArgument($this->config->sync['ssh']);

        foreach ($mysqlConf['database'] as $databaseConf) {
            list($localDatabase, $foreignDatabase) = explode(':', $databaseConf, 2);

            $dumpFile = $this->tempDir . '/' . $localDatabase . '.sql.bz2';

            // ##########
            // Dump from server
            // ##########
            $this->output->writeln('<info>Fetching foreign database ' . $foreignDatabase . '</info>');

            $mysqldump = clone $mysqlDumpTemplate;
            $mysqldump->addArgument($foreignDatabase);

            $command = $this->wrapCommand($mysqldump);

            $command->setOutputRedirectToFile($dumpFile);

            $command->executeInteractive();

            // ##########
            // Restore local
            // ##########
            $this->output->writeln('<info>Restoring database ' . $localDatabase . '</info>');

            $mysqldump = new SelfCommandBuilder();
            $mysqldump->addArgumentTemplate('mysql:restore %s %s', $localDatabase, $dumpFile);
            $mysqldump->executeInteractive();
        }
    }

    /**
     * Wrap command with ssh if needed
     *
     * @param  CommandBuilderInterface $command
     * @return CommandBuilderInterface
     */
    protected function wrapCommand(CommandBuilderInterface $command) {
        // Wrap in ssh if available
        if (!empty($this->config->sync['ssh'])) {
            $sshCommand = new CommandBuilder('ssh');
            $sshCommand->addArgument($this->config->sync['ssh'])
                ->append($command, true);

            $command = $sshCommand;
        }

        return $command;
    }

    /**
     * Create rsync command for share sync
     *
     * @return CommandBuilder
     */
    protected function createSyncRsyncCommand($source, $target, $useExcludeInclude = false) {
        $this->output->writeln('<info>Sync from ' . $source . ' to ' . $target . '</info>');

        $command = new CommandBuilder('rsync', '-rlptD --delete-after');

        if ($useExcludeInclude && !empty($this->config->share['rsync']['directory'])) {

            // Add file list (external file with --files-from option)
            if (!empty($this->config->sync['rsync']['directory'])) {
                $this->rsyncAddFileList($command, $this->config->sync['rsync']['directory']);
            }

            // Add exclude (external file with --exclude-from option)
            if (!empty($this->config->sync['rsync']['exclude'])) {
                $this->rsyncAddExcludeList($command, $this->config->sync['rsync']['exclude']);
            }
        }

        // Paths should have leading / to prevent sync issues
        $source = rtrim($source, '/') . '/';
        $target = rtrim($target, '/') . '/';

        // Set source and target
        $command->addArgument($source)
                ->addArgument($target);

        return $command;
    }

}
