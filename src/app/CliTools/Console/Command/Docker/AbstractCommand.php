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

use CliTools\Console\Builder\CommandBuilder;

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
            $this->dockerPath = \CliTools\Utility\DockerUtility::searchDockerDirectoryRecursive();
            $this->output->writeln('<comment>Found docker directory: ' . $this->dockerPath . '</comment>');
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
            $this->output->writeln('<error>No container specified</error>');
            return false;
        }

        if (empty($envName)) {
            $this->output->writeln('<error>No environment name specified</error>');
            return false;
        }

        $path = $this->getDockerPath();

        if (!empty($path)) {
            $dockerContainerName = \CliTools\Utility\DockerUtility::getDockerInstanceName($containerName, 1, $path);

            chdir($path);

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
     * @param  string         $containerName Container name
     * @param  CommandBuilder $comamnd       Command
     *
     * @return int|null|void
     */
    protected function executeDockerExec($containerName, CommandBuilder $command) {
        if (empty($containerName)) {
            $this->output->writeln('<error>No container specified</error>');
            return 1;
        }

        if (!$command->isExecuteable()) {
            $this->output->writeln('<error>No command specified or not executeable</error>');
            return 1;
        }

        $path = $this->getDockerPath();

        if (!empty($path)) {
            $dockerContainerName = \CliTools\Utility\DockerUtility::getDockerInstanceName($containerName, 1, $path);

            chdir($path);

            $this->output->writeln('<info>Executing "' . $command->getCommand() . '" in docker container "' . $dockerContainerName . '" ...</info>');

            $dockerCommand = new CommandBuilder('docker', 'exec -ti %s', array($dockerContainerName));
            $dockerCommand->append($command, false);
            $dockerCommand->executeInteractive();
        } else {
            $this->output->writeln('<error>No docker-compose.yml found in tree</error>');

            return 1;
        }

        return 0;
    }

    /**
     * Execute docker compose run
     *
     * @param  null|CommandBuilder $command   Command
     *
     * @return int|null|void
     */
    protected function executeDockerCompose(CommandBuilder $command = null) {
        $path = \CliTools\Utility\DockerUtility::searchDockerDirectoryRecursive();

        if (!empty($path)) {
            $this->output->writeln('<comment>Found docker directory: ' . $path . '</comment>');
            chdir($path);

            $command->setCommand('docker-compose');
            $command->executeInteractive();
        } else {
            $this->output->writeln('<error>No docker-compose.yml found in tree</error>');

            return 1;
        }

        return 0;
    }

    /**
     * Execute docker compose run
     *
     * @param  string          $containerName Container name
     * @param  CommandBuilder  $command       Command
     *
     * @return int|null|void
     */
    protected function executeDockerComposeRun($containerName, CommandBuilder $command) {
        $path = $this->getDockerPath();

        if (!empty($path)) {
            chdir($path);

            $this->output->writeln('<info>Executing "' . $command->getCommand() . '" in docker container "' . $containerName . '" ...</info>');

            $dockerCommand = new CommandBuilder('docker-compose', 'run --rm %s', array($containerName));
            $dockerCommand->append($command, false);
            $dockerCommand->executeInteractive();
        } else {
            $this->output->writeln('<error>No docker-compose.yml found in tree</error>');

            return 1;
        }

        return 0;
    }
}
