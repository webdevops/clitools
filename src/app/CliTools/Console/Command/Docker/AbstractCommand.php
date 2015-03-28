<?php

namespace CliTools\Console\Command\Docker;

/**
 * CliTools Command
 * Copyright (C) 2014 Markus Blaschke <markus@familie-blaschke.net>
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

use CliTools\Utility\CommandExecutionUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Execute docker command
     *
     * @param  string $containerName Container name
     * @param  string $cmd           Command
     * @return int|null|void
     */
    protected function executeDockerExec($containerName, $cmd) {
        if(\CliTools\Utility\DockerUtility::isDockerDirectory()) {
            $dockerName = \CliTools\Utility\DockerUtility::getDockerInstanceName($containerName);

            $this->output->writeln('<info>Execeuting "' . $cmd .'" in docker container "' . $dockerName . '" ...</info>');

            CommandExecutionUtility::passthru('docker', 'exec -ti %s %s', array($dockerName, $cmd));
        } else {
            $this->output->writeln('<error>No docker instance found in this directory</error>');
        }

        return 0;
    }


}