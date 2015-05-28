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

use Symfony\Component\Console\Input\InputOption;

class RestoreCommand extends AbstractShareCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('sync:restore')
            ->setDescription('Restore project files')
            ->addOption(
                'mysql',
                null,
                InputOption::VALUE_NONE,
                'Run only mysql'
            )
            ->addOption(
                'rsync',
                null,
                InputOption::VALUE_NONE,
                'Run only rsync'
            );
    }

    /**
     * Restore task
     */
    protected function runTask() {
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
        // Restore dirs
        // ##################
        if ($runRsync && $this->config->exists('rsync.directory')) {
            $this->runTaskRsync();
        }

        // ##################
        // Restore mysql dump
        // ##################
        if ($runMysql) {
            $this->runTaskMysql();
        }
    }

    /**
     * Sync files with rsync
     */
    protected function runTaskRsync() {
        $source  = $this->getRsyncPathFromConfig() . self::PATH_DUMP;
        $target  = $this->getRsyncWorkingPath();
        $command = $this->createShareRsyncCommand($source, $target, true);
        $command->executeInteractive();
    }


    /**
     * Sync files with mysql
     */
    protected function runTaskMysql() {
        $source  = $this->getRsyncPathFromConfig() . self::PATH_DUMP;
        $target  = $this->tempDir;
        $command = $this->createShareRsyncCommand($source, $target, false);
        $command->executeInteractive();

        $iterator = new \DirectoryIterator($this->tempDir . '/mysql');
        foreach ($iterator as $item) {
            // skip dot
            if ($item->isDot()) {
                continue;
            }

            list($database) = explode('.', $item->getFilename(), 2);

            if (!empty($database)) {
                $this->output->writeln('<info>Restoring database ' . $database . '</info>');

                $this->createMysqlRestoreCommand($database, $item->getPathname())->executeInteractive();
            }
        }
    }
}
