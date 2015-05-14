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

use CliTools\Console\Builder\SelfCommandBuilder;

class BackupCommand extends \CliTools\Console\Command\Sync\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('sync:backup')
             ->setDescription('Backup project files');
    }

    /**
     * Backup task
     */
    protected function runTask() {
        // ##################
        // Backup dirs
        // ##################
        $source  = $this->workingPath;
        $target  = $this->config->share['rsync']['target'] . self::PATH_DATA;
        $command = $this->createShareRsyncCommand($source, $target, true);
        $command->executeInteractive();

        // ##################
        // Backup databases
        // ##################
        if (!empty($this->config->share['mysql']) && !empty($this->config->share['mysql']['database'])) {
            foreach ($this->config->share['mysql']['database'] as $database) {
                $this->output->writeln('<info>Dumping database ' . $database . '</info>');

                // dump database
                $dumpFile = $this->tempDir . '/mysql/' . $database . '.sql.bz2';

                $mysqldump = new SelfCommandBuilder();
                $mysqldump->addArgumentTemplate('mysql:backup %s %s', $database, $dumpFile);

                if (!empty($this->config->share['mysql']['filter'])) {
                    $mysqldump->addArgumentTemplate('--filter=%s', $this->config->share['mysql']['filter']);
                }

                $mysqldump->executeInteractive();
            }

            // ##################
            // Backup mysql dump
            // ##################
            $source = $this->tempDir;
            $target = $this->config->share['rsync']['target'] . self::PATH_DUMP;
            $command = $this->createShareRsyncCommand($source, $target, false);
            $command->executeInteractive();
        }

    }

}
