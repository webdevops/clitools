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

use CliTools\Console\Shell\CommandBuilder\CommandBuilder;
use CliTools\Console\Shell\CommandBuilder\RemoteCommandBuilder;
use CliTools\Console\Shell\CommandBuilder\CommandBuilderInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;

abstract class AbstractSyncCommand extends \CliTools\Console\Command\Sync\AbstractCommand {
    
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

        if ($this->config->exists('ssh')) {
            // Check if one database is configured
            if (!$this->config->exists('ssh.hostname')) {
                $output->writeln('<p-error>No ssh hostname configuration found</p-error>');
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     * Read and validate configuration
     */
    protected function readConfiguration() {
        parent::readConfiguration();

        $this->contextName = $this->getServerContext();

        if (empty($this->contextName) || $this->contextName === '_' || empty($this->config[$this->contextName])) {
            throw new \RuntimeException('No valid configuration found for context "' . $this->contextName . '"');
        }

        // Use server specific configuration
        $this->output->writeln('<p>Syncing from "' . $this->contextName . '" server</p>');

        // ##################
        // Jump into section
        // ##################
        if ($this->config->exists('_')) {
            // Merge global config with specific config
            $this->config->setData(array_replace_recursive($this->config->getArray('_'), $this->config->getArray($this->contextName)));
        } else {
            $this->config->setData($this->config->getArray($this->contextName));
        }
    }

    /**
     * Get server context from user
     */
    protected function getServerContext() {
        $ret = null;

        if (!$this->input->getArgument('context')) {
            // ########################
            // Ask user for server context
            // ########################

            $serverList = $this->config->getList();
            $serverList = array_diff($serverList, array('_'));

            if (empty($serverList)) {
                throw new \RuntimeException('No valid servers found in configuration');
            }

            $serverOptionList = array();

            foreach ($serverList as $context) {
                $line = array();

                // hostname
                $optPath = $context . '.ssh.hostname';
                if ($this->config->exists($optPath)) {
                    $line[] = '<info>host:</info>' . $this->config->get($optPath);
                }

                // rsync path
                $optPath = $context . '.rsync.path';
                if ($this->config->exists($optPath)) {
                    $line[] = '<info>rsync:</info>' . $this->config->get($optPath);
                }

                // mysql database list
                $optPath = $context . '.mysql.database';
                if ($this->config->exists($optPath)) {
                    $dbList        = $this->config->getArray($optPath);
                    $foreignDbList = array();

                    foreach ($dbList as $databaseConf) {
                        if (strpos($databaseConf, ':') !== false) {
                            // local and foreign database in one string
                            list($localDatabase, $foreignDatabase) = explode(':', $databaseConf, 2);
                            $foreignDbList[] = $foreignDatabase;
                        } else {
                            // database equal
                            $foreignDbList[] = $databaseConf;
                        }
                    }

                    if (!empty($foreignDbList)) {
                        $line[] .= '<info>mysql:</info>' . implode(', ', $foreignDbList);
                    }
                }

                if (!empty($line)) {
                    $line = implode(' ', $line);
                } else {
                    // fallback
                    $line = $context;
                }

                $serverOptionList[$context] = $line;
            }

            try {
                $question = new ChoiceQuestion('Please choose server context for synchronization', $serverOptionList);
                $question->setMaxAttempts(1);

                $questionDialog = new QuestionHelper();

                $ret = $questionDialog->ask($this->input, $this->output, $question);
            } catch(\InvalidArgumentException $e) {
                // Invalid server context, just stop here
                throw new \CliTools\Exception\StopException(1);
            }
        } else {
            $ret = $this->input->getArgument('context');
        }

        return $ret;
    }

    /**
     * Wrap command with ssh if needed
     *
     * @param  CommandBuilderInterface $command
     * @return CommandBuilderInterface
     */
    protected function wrapRemoteCommand(CommandBuilderInterface $command) {
        // Wrap in ssh if needed
        if ($this->config->exists('ssh.hostname')) {
            $sshCommand = new CommandBuilder('ssh', '-o BatchMode=yes');
            $sshCommand->addArgument($this->config->get('ssh.hostname'))
                       ->append($command, true);

            $command = $sshCommand;
        }

        return $command;
    }

    /**
     * Create new mysql command
     *
     * @param null|string $database Database name
     *
     * @return RemoteCommandBuilder
     */
    protected function createRemoteMySqlCommand($database = null) {
        $command = new RemoteCommandBuilder('mysql');
        $command
            // batch mode
            ->addArgument('-B')
            // skip column names
            ->addArgument('-N');

        // Add username
        if ($this->config->exists('mysql.username')) {
            $command->addArgumentTemplate('-u%s', $this->config->get('mysql.username'));
        }

        // Add password
        if ($this->config->exists('mysql.password')) {
            $command->addArgumentTemplate('-p%s', $this->config->get('mysql.password'));
        }

        // Add hostname
        if ($this->config->exists('mysql.hostname')) {
            $command->addArgumentTemplate('-h%s', $this->config->get('mysql.hostname'));
        }

        if ($database !== null) {
            $command->addArgument($database);
        }

        return $command;
    }

    /**
     * Create new mysql command
     *
     * @param null|string $database Database name
     *
     * @return RemoteCommandBuilder
     */
    protected function createRemoteMySqlDumpCommand($database = null) {
        $command = new RemoteCommandBuilder('mysqldump');

        // Add username
        if ($this->config->exists('mysql.username')) {
            $command->addArgumentTemplate('-u%s', $this->config->get('mysql.username'));
        }

        // Add password
        if ($this->config->exists('mysql.password')) {
            $command->addArgumentTemplate('-p%s', $this->config->get('mysql.password'));
        }

        // Add hostname
        if ($this->config->exists('mysql.hostname')) {
            $command->addArgumentTemplate('-h%s', $this->config->get('mysql.hostname'));
        }

        // Add custom options
        if ($this->config->exists('mysqldump.option')) {
            $command->addArgumentRaw($this->config->get('mysqldump.option'));
        }

        // Transfer compression
        switch($this->config->get('mysql.compression')) {
            case 'bzip2':
                // Add pipe compressor (bzip2 compressed transfer via ssh)
                $command->addPipeCommand( new CommandBuilder('bzip2', '--compress --stdout') );
                break;

            case 'gzip':
                // Add pipe compressor (gzip compressed transfer via ssh)
                $command->addPipeCommand( new CommandBuilder('gzip', '--stdout') );
                break;
        }


        if ($database !== null) {
            $command->addArgument($database);
        }

        return $command;
    }


}
