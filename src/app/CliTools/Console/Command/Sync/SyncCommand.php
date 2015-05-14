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

use CliTools\Utility\FilterUtility;
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
            // Full mysql transfer
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
        $command = $this->createRsyncCommand($source, $target);

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

            if ($this->config->sync['mysql']['filter']) {
                $mysqldump = $this->addFilterArguments($mysqldump, $foreignDatabase, $this->config->sync['mysql']['filter']);
            }

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
     * @param string     $source    Source directory
     * @param string     $target    Target directory
     * @param array|null $filelist  List of files (patterns)
     * @param array|null $exclude   List of excludes (patterns)
     *
     * @return CommandBuilder
     */
    protected function createRsyncCommand($source, $target, array $filelist = null, array $exclude = null) {
        // Add file list (external file with --files-from option)
        if (!$filelist && !empty($this->config->sync['rsync']['directory'])) {
            $filelist = $this->config->sync['rsync']['directory'];
        }

        // Add exclude (external file with --exclude-from option)
        if (!$exclude && !empty($this->config->sync['rsync']['exclude'])) {
            $exclude = $this->config->sync['rsync']['exclude'];
        }

        return parent::createRsyncCommand($source, $target, $filelist, $exclude);
    }

    /**
     * Add filter to command
     *
     * @param CommandBuilderInterface $command  Command
     * @param string                  $database Database
     * @param string                  $filter   Filter name
     *
     * @return CommandBuilderInterface
     */
    protected function addFilterArguments(CommandBuilderInterface $commandDump, $database, $filter) {
        $command = $commandDump;

        // get filter
        $filterList = $this->getApplication()->getConfigValue('mysql-backup-filter', $filter);

        if (empty($filterList)) {
            throw new \RuntimeException('MySQL dump filters "' . $filter . '" not available"');
        }

        $this->output->writeln('<comment>Using filter "' . $filter . '"</comment>');

        // Get table list
        $tableListDumper = clone $commandDump;
        $tableListDumper->setCommand('mysql')
            ->addArgument('--skip-column-names')
            ->addArgumentTemplate('-e %s', 'show tables;')
            ->clearOutputRedirect()
            ->clearPipes();

        $tableListDumper = $this->wrapCommand($tableListDumper);
        $tableList       = $tableListDumper->execute()->getOutput();

        // Filter table list
        $tableList = FilterUtility::mysqlTableFilter($tableList, $filterList);

        // Dump only structure
        $commandStructure = clone $command;
        $commandStructure->addArgument('--no-data');

        // Dump only data (only filtered tables)
        $commandData = clone $command;
        $commandData
            ->addArgument('--no-create-info')
            ->addArgumentList($tableList);

        // Combine both commands to one
        $command = new \CliTools\Console\Builder\OutputCombineCommandBuilder();
        $command
            ->addCommandForCombinedOutput($commandStructure)
            ->addCommandForCombinedOutput($commandData);

        return $command;
    }

}
