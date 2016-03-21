<?php

namespace CliTools\Console\Command\Docker;

/*
 * CliTools Command
 * Copyright (C) 2016 WebDevOps.io
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

use CliTools\Shell\CommandBuilder\CommandBuilder;
use CliTools\Shell\CommandBuilder\CommandBuilderInterface;
use CliTools\Utility\DockerUtility;
use CliTools\Utility\PhpUtility;

abstract class AbstractCommand extends \CliTools\Console\Command\AbstractCommand
{

    /**
     * Docker path
     *
     * @var null|string
     */
    protected $dockerPath;

    /**
     * @var array
     */
    protected $runningContainerCache = array();

    /**
     * Search and return for updir docker path
     *
     * @return bool|null|string
     */
    protected function getDockerPath()
    {
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
     * Find and get docker environment variable
     *
     * @param  string $containerName Container name
     * @param  array  $envNameList   Environment variable (list)
     *
     * @return string|bool|null
     */
    protected function findAndGetDockerEnv($containerName, array $envNameList)
    {
        $ret = null;

        foreach ($envNameList as $envName) {
            if ($tmp = $this->getDockerEnv($containerName, $envName) ) {
                $ret = $tmp;
                break;
            }
        }

        return $ret;
    }

    /**
     * Get docker environment variable
     *
     * @param  string $containerName Container name
     * @param  string $envName       Environment variable
     *
     * @return string|bool|null
     */
    protected function getDockerEnv($containerName, $envName)
    {
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
            $dockerContainerName = $this->findAndBuildContainerName($containerName);

            // Switch to directory of docker-compose.yml
            PhpUtility::chdir($path);

            // Get docker confguration (fetched directly from docker)
            $conf = \CliTools\Utility\DockerUtility::getDockerConfiguration($dockerContainerName);

            if (empty($conf)) {
                throw new \RuntimeException(
                    'Could not read docker configuration from container  "' . $dockerContainerName . '"'
                );
            }

            if (!empty($conf->Config->Env[$envName])) {
                $ret = $conf->Config->Env[$envName];
            }
        }

        return $ret;
    }

    /**
     * Check if docker container is available
     *
     * @param  string $containerName Container name
     *
     * @return string|bool|null
     */
    protected function checkIfDockerContainerIsAvailable($containerName)
    {
        $ret = null;

        if (empty($containerName)) {
            $this->output->writeln('<p-error>No container specified</p-error>');

            return false;
        }

        try {
            // Search updir for docker-compose.yml
            $path = $this->getDockerPath();

            if (!empty($path)) {
                // Genrate full docker container name
                $dockerContainerName = \CliTools\Utility\DockerUtility::getDockerInstanceName($containerName, 1, $path);

                // Switch to directory of docker-compose.yml
                PhpUtility::chdir($path);

                // Get docker confguration (fetched directly from docker)
                $conf = \CliTools\Utility\DockerUtility::getDockerConfiguration($dockerContainerName);

                if (!empty($conf)
                    && !empty($conf->State)
                    && !empty($conf->State->Status)
                    && in_array(strtolower($conf->State->Status), ['running'], true)
                ) {
                    $ret = true;
                }
            }
        } catch (\Exception $e) {
            $ret = false;
        }

        return $ret;
    }

    /**
     * Execute docker command
     *
     * @param  string                  $containerName Container name
     * @param  CommandBuilderInterface $comamnd       Command
     * @param  callback|null           $dockerCommandCallback   Docker command callback
     *
     * @return int|null|void
     */
    protected function executeDockerExec($containerName, CommandBuilderInterface $command, callable $dockerCommandCallback = null)
    {
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
            $dockerContainerName = $this->findAndBuildContainerName($containerName);

            // Switch to directory of docker-compose.yml
            PhpUtility::chdir($path);

            $this->output->writeln(
                '<info>Executing "' . $command->getCommand(
                ) . '" in docker container "' . $dockerContainerName . '" ...</info>'
            );

            $dockerCommand = new CommandBuilder('docker', 'exec -ti');
            if ($dockerCommandCallback) {
                $dockerCommandCallback($dockerCommand);
            }
            $dockerCommand->addArgument($dockerContainerName);

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
     * @param  null|CommandBuilderInterface $command Command
     *
     * @return int|null|void
     */
    protected function executeDockerCompose(CommandBuilderInterface $command = null)
    {
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
     * @param  string                  $containerName Container name
     * @param  CommandBuilderInterface $command       Command
     *
     * @return int|null|void
     */
    protected function executeDockerComposeRun($containerName, CommandBuilderInterface $command)
    {
        // Search updir for docker-compose.yml
        $path = $this->getDockerPath();

        if (!empty($path)) {
            // Switch to directory of docker-compose.yml
            PhpUtility::chdir($path);

            $this->output->writeln(
                '<info>Executing "' . $command->getCommand(
                ) . '" in docker container "' . $containerName . '" ...</info>'
            );

            $dockerCommand = new CommandBuilder('docker-compose', 'run --rm %s', array($containerName));
            $dockerCommand->append($command, false);
            $dockerCommand->executeInteractive();
        } else {
            $this->output->writeln('<p-error>No docker-compose.yml found in tree</p-error>');

            return 1;
        }

        return 0;
    }

    /**
     * @param null $containerName Poissible Container name (csv)
     * @return null|string
     */
    protected function findAndBuildContainerName($containerName = null)
    {
        // Use cached container name
        if (isset($this->runningContainerCache[$containerName])) {
            return $this->runningContainerCache[$containerName];
        }

        $fullContainerName = null;

        $path = $this->getDockerPath();
        $oldPath = getcwd();

        chdir($path);

        $containerNameList = PhpUtility::trimExplode(',', $containerName);
        foreach ($containerNameList as $containerNameToTry) {
            try {
                $command = new CommandBuilder('docker-compose');
                $command
                    ->addArgumentTemplate('ps -q %s', $containerNameToTry)
                    ->setOutputRedirect(CommandBuilder::OUTPUT_REDIRECT_NO_STDERR);
                $fullContainerName = $command->execute()->getOutputString();
                break;
            } catch (\Exception $e) {
                // container not running
                continue;
            }
        }

        chdir($oldPath);

        if (empty($fullContainerName)) {
            throw new \RuntimeException('No running docker container found, tried: ' . implode(', ', $containerNameList));
        }

        // Cache running container
        $this->runningContainerCache[$containerName] = $fullContainerName;

        return $fullContainerName;
    }
}
