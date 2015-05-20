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

class BackupCommand extends AbstractShareCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('sync:backup')
            ->setDescription('Backup project files');
    }

    /**
     * Backup task
     */
    protected function runTask() {
        // ##################
        // Backup dirs
        // ##################
        if ($this->config->exists('rsync.directory')) {
            $source  = $this->getRsyncWorkingPath();
            $target  = $this->getRsyncPathFromConfig() . self::PATH_DATA;
            $command = $this->createShareRsyncCommand($source, $target, true);
            $command->executeInteractive();
        }

        // ##################
        // Backup databases
        // ##################
        if ($this->config->exists('mysql.database')) {
            foreach ($this->config->getArray('mysql.database') as $database) {
                $this->output->writeln('<info>Dumping database ' . $database . '</info>');

                // dump database
                $dumpFile = $this->tempDir . '/mysql/' . $database . '.sql.bz2';

                $dumpFilter = $this->config->get('mysql.filter');

                $this->createMysqlBackupCommand($database, $dumpFile, $dumpFilter)
                    ->executeInteractive();
            }

            // ##################
            // Backup mysql dump
            // ##################
            $source = $this->tempDir;
            $target = $this->getRsyncPathFromConfig() . self::PATH_DUMP;
            $command = $this->createShareRsyncCommand($source, $target, false);
            $command->executeInteractive();
        }

    }

}
