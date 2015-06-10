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
