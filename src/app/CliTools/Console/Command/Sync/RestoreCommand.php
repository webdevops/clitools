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

class RestoreCommand extends AbstractShareCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('sync:restore')
             ->setDescription('Restore files and database from share');
    }

    /**
     * Startup task
     */
    protected function startup()
    {
        $this->output->writeln('<h2>Starting share restore</h2>');
        parent::startup();
    }

    /**
     * Restore task
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
        // Restore dirs
        // ##################
        if ($runRsync && $this->contextConfig->exists('rsync.directory')) {
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
    protected function runTaskRsync()
    {
        $source = $this->getRsyncPathFromConfig() . self::PATH_DATA;
        $target = $this->getRsyncWorkingPath();

        $command = $this->createRsyncCommandWithConfiguration($source, $target, 'rsync');
        $command->executeInteractive();
    }

    /**
     * Sync files with mysql
     */
    protected function runTaskMysql()
    {
        $source  = $this->getRsyncPathFromConfig() . self::PATH_DUMP;
        $target  = $this->tempDir;
        $command = $this->createRsyncCommand($source, $target);
        $command->executeInteractive();

        $iterator = new \DirectoryIterator($this->tempDir . '/mysql');
        foreach ($iterator as $item) {
            // skip dot
            if ($item->isDot()) {
                continue;
            }

            list($database) = explode('.', $item->getFilename(), 2);

            if (!empty($database)) {
                $this->output->writeln('<h1>Restoring database ' . $database . '</h1>');

                $this->createMysqlRestoreCommand($database, $item->getPathname())
                     ->executeInteractive();
            }
        }
    }
}
