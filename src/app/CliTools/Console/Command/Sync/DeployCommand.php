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
use CliTools\Console\Shell\CommandBuilder\CommandBuilder;
use CliTools\Console\Shell\CommandBuilder\RemoteCommandBuilder;
use CliTools\Console\Shell\CommandBuilder\OutputCombineCommandBuilder;
use CliTools\Console\Shell\CommandBuilder\CommandBuilderInterface;
use CliTools\Database\DatabaseConnection;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class DeployCommand extends AbstractRemoteSyncCommand {

    /**
     * Config area
     *
     * @var string
     */
    protected $confArea = 'deploy';

    /**
     * Server configuration name
     * @var string
     */
    protected $contextName;

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('sync:deploy')
            ->setDescription('Deploy files and database to server')
            ->addArgument(
                'context',
                InputArgument::OPTIONAL,
                'Configuration name for server'
            )
            ->addOption(
                'rsync',
                null,
                InputOption::VALUE_NONE,
                'Run only rsync'
            );
    }

    /**
     * Startup task
     */
    protected function startup() {
        $this->output->writeln('<h2>Starting server deployment</h2>');
        parent::startup();
    }

    /**
     * Backup task
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
        // Run tasks
        // ##################

        // Check database connection
        if ($runMysql && $this->config->exists('mysql')) {
            DatabaseConnection::ping();
        }

        // Sync files with rsync to local storage
        if ($runRsync && $this->config->exists('rsync')) {
            $this->output->writeln('<h1>Starting FILE deployment</h1>');
            $this->runTaskRsync();
        }

        // Sync database to local server
        if ($runMysql && $this->config->exists('mysql')) {
            $this->output->writeln('<h1>Starting MYSQL deployment</h1>');
            $this->output->writeln('<p>TODO - not implemented</h1>');
        }
    }

    /**
     * Sync files with rsync
     */
    protected function runTaskRsync() {
        // ##################
        // Restore dirs
        // ##################
        $source = $this->getRsyncWorkingPath();
        $target = $this->getRsyncPathFromConfig();
        $command = $this->createRsyncCommand($source, $target);

        $command->executeInteractive();
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
        if (!$filelist && $this->config->exists('rsync.directory')) {
            $filelist = $this->config->get('rsync.directory');
        }

        // Add exclude (external file with --exclude-from option)
        if (!$exclude && $this->config->exists('rsync.exclude')) {
            $exclude = $this->config->get('rsync.exclude');
        }

        return parent::createRsyncCommand($source, $target, $filelist, $exclude);
    }

}
