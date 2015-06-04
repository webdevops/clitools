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

use CliTools\Console\Shell\CommandBuilder\CommandBuilder;
use CliTools\Console\Shell\CommandBuilder\CommandBuilderInterface;
use CliTools\Utility\PhpUtility;

abstract class AbstractCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Docker path
     *
     * @var null|string
     */
    protected $dockerPath;

    /**
     * Search and return for updir docker path
     *
     * @return bool|null|string
     */
    protected function getDockerPath() {
        if ($this->dockerPath === null) {
            $composePath = \CliTools\Utility\DockerUtility::searchDockerDirectoryRecursive();

            if (!empty($composePath)) {
                $this->dockerPath = dirname($composePath);
                $this->output->writeln('<comment>Found docker directory: ' . $this->dockerPath . '</comment>');
            }
        }

        return $this->dockerPath;
    }

    /**
     * Execute docker command
     *
     * @param  string $containerName Container name
     * @param  string $envName       Environment variable
     *
     * @return string|bool|null
     */
    protected function getDockerEnv($containerName, $envName) {
        $ret = null;

        if (empty($containerName)) {
            $this->output->writeln('<p-error>No container specified</p-error>');
            return false;
        }

        if (empty($envName)) {
            $this->output->writeln('<p-error>No environment name specified</p-error>');
            return false;
        }

        // Search updir for docker-compose.yml
        $path = $this->getDockerPath();

        if (!empty($path)) {
            // Genrate full docker container name
            $dockerContainerName = \CliTools\Utility\DockerUtility::getDockerInstanceName($containerName, 1, $path);

            // Switch to directory of docker-compose.yml
            PhpUtility::chdir($path);

            // Get docker confguration (fetched directly from docker)
            $conf = \CliTools\Utility\DockerUtility::getDockerConfiguration($dockerContainerName);

            if (empty($conf)) {
                throw new \RuntimeException('Could not read docker configuration from container  "' . $dockerContainerName . '"');
            }

            if (!empty($conf->Config->Env[$envName])) {
                $ret = $conf->Config->Env[$envName];
            }
        }

        return $ret;
    }

    /**
     * Execute docker command
     *
     * @param  string                  $containerName Container name
     * @param  CommandBuilderInterface $comamnd       Command
     *
     * @return int|null|void
     */
    protected function executeDockerExec($containerName, CommandBuilderInterface $command) {
        if (empty($containerName)) {
            $this->output->writeln('<p-error>No container specified</p-error>');
            return 1;
        }

        if (!$command->isExecuteable()) {
            $this->output->writeln('<p-error>No command specified or not executeable</p-error>');
            return 1;
        }

        // Search updir for docker-compose.yml
        $path = $this->getDockerPath();

        if (!empty($path)) {
            // Genrate full docker container name
            $dockerContainerName = \CliTools\Utility\DockerUtility::getDockerInstanceName($containerName, 1, $path);

            // Switch to directory of docker-compose.yml
            PhpUtility::chdir($path);

            $this->output->writeln('<info>Executing "' . $command->getCommand() . '" in docker container "' . $dockerContainerName . '" ...</info>');

            $dockerCommand = new CommandBuilder('docker', 'exec -ti %s', array($dockerContainerName));
            $dockerCommand->append($command, false);
            $dockerCommand->executeInteractive();
        } else {
            $this->output->writeln('<p-error>No docker-compose.yml found in tree</p-error>');

            return 1;
        }

        return 0;
    }

    /**
     * Execute docker compose run
     *
     * @param  null|CommandBuilderInterface $command   Command
     *
     * @return int|null|void
     */
    protected function executeDockerCompose(CommandBuilderInterface $command = null) {
        // Search updir for docker-compose.yml
        $path = \CliTools\Utility\DockerUtility::searchDockerDirectoryRecursive();

        if (!empty($path)) {
            $path = dirname($path);
            $this->output->writeln('<comment>Found docker directory: ' . $path . '</comment>');

            // Switch to directory of docker-compose.yml
            PhpUtility::chdir($path);

            $command->setCommand('docker-compose');
            $command->executeInteractive();
        } else {
            $this->output->writeln('<p-error>No docker-compose.yml found in tree</p-error>');

            return 1;
        }

        return 0;
    }

    /**
     * Execute docker compose run
     *
     * @param  string          $containerName Container name
     * @param  CommandBuilderInterface  $command       Command
     *
     * @return int|null|void
     */
    protected function executeDockerComposeRun($containerName, CommandBuilderInterface $command) {
        // Search updir for docker-compose.yml
        $path = $this->getDockerPath();

        if (!empty($path)) {
            // Switch to directory of docker-compose.yml
            PhpUtility::chdir($path);

            $this->output->writeln('<info>Executing "' . $command->getCommand() . '" in docker container "' . $containerName . '" ...</info>');

            $dockerCommand = new CommandBuilder('docker-compose', 'run --rm %s', array($containerName));
            $dockerCommand->append($command, false);
            $dockerCommand->executeInteractive();
        } else {
            $this->output->writeln('<p-error>No docker-compose.yml found in tree</p-error>');

            return 1;
        }

        return 0;
    }
}
