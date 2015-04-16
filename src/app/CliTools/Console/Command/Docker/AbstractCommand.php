<?php

namespace CliTools\Console\Command\Docker;

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
        $path = \CliTools\Utility\DockerUtility::searchDockerDirectoryRecursive();

        if (!empty($path)) {
            $dockerContainerName = \CliTools\Utility\DockerUtility::getDockerInstanceName($containerName, 1, $path);

            $this->output->writeln('<comment>Found docker directory: ' . $path . '</comment>');
            chdir($path);

            $this->output->writeln('<info>Execeuting "' . $cmd . '" in docker container "' . $dockerContainerName . '" ...</info>');

            CommandExecutionUtility::execInteractive('docker', 'exec -ti %s %s', array($dockerContainerName, $cmd));
        } else {
            $this->output->writeln('<error>No docker-compose.yml found in tree</error>');
            return 1;
        }

        return 0;
    }


}
