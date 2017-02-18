<?php

namespace CliTools\Console\Command;

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

use CliTools\Database\DatabaseConnection;
use CliTools\Shell\CommandBuilder\CommandBuilder;
use CliTools\Shell\CommandBuilder\DockerExecCommandBuilder;
use CliTools\Shell\CommandBuilder\FullSelfCommandBuilder;
use CliTools\Utility\ConsoleUtility;
use CliTools\Utility\DockerUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDockerCommand extends AbstractCommand
{

    const DOCKER_ALIAS_MYSQL = 'mysql';

    /**
     * Docker container
     */
    protected $dockerContainer = [];

    /**
     * @return mixed
     */
    public function getLocalDockerContainer($alias)
    {
        if (!empty($this->dockerContainer['local'][$alias])) {
            return $this->dockerContainer['local'][$alias];
        }

        return false;
    }

    /**
     * @param string $dockerContainer
     * @param boolean $dockerComposeLookup Use docker compose lookup
     * @return AbstractCommand
     */
    public function setLocalDockerContainer($alias, $dockerContainer, $dockerComposeLookup = false)
    {
        if ($dockerComposeLookup) {
            $dockerComposeContainer = $dockerContainer;
            $dockerContainer = DockerUtility::lookupDockerComposeContainerId($dockerContainer);

            $this->output->writeln(sprintf(
                '<p>Using local Docker-Compose container %s (ID: %s) for %s usage</p>',
                $dockerComposeContainer,
                $dockerContainer,
                $alias
            ));
        } else {
            $this->output->writeln(sprintf(
                '<p>Using local Docker container ID %s for %s usage</p>',
                $dockerContainer,
                $alias
            ));
        }

        $this->dockerContainer['local'][$alias] = $dockerContainer;
        return $this;
    }

    /**
     * MySQL quote (for use in commands)
     *
     * @param string $value
     * @return string
     */
    protected function mysqlQuote($value) {
        return '\'' . addslashes($value) . '\'';
    }

    /**
     * Execute sql command (using local mysql command)
     *
     * @param string $sql
     * @return array|null
     */
    protected function execSqlCommand($sql)
    {
        return $this->createMysqlCommand('-e', $sql)->execute()->getOutput();
    }


    /**
     * Execute sql command (using local mysql command)
     *
     * @param string $sql
     * @param boolean $assoc associate array
     * @return array|null
     */
    protected function execSqlQuery($sql, $assoc = true)
    {
        $delimiter = "\t";
        $ret = array();
        $result = $this->createMysqlCommand('--column-names', '-e', $sql)->execute()->getOutput();

        if (empty($result)) {
            return [];
        }

        $columnList = explode($delimiter, $result[0]);
        unset($result[0]);

        foreach ($result as $line) {
            $values = explode($delimiter, $line);

            if ($assoc) {
                $ret[] = array_combine($columnList, $values);
            } else {
                $ret[] = $values;
            }
        }

        return $ret;
    }

    /**
     * Fetch list of mysql tables
     *
     * @param string $database MySQL database
     * @return array|null
     */
    protected function mysqlTableList($database)
    {
        $sql = sprintf(
            'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s',
            $this->mysqlQuote($database)
        );
        return $this->execSqlCommand($sql);
    }

    /**
     * Fetch list of mysql databases
     *
     * @return array|null
     */
    protected function mysqlDatabaseList()
    {
        $sql = 'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA';
        $ret = $this->execSqlCommand($sql);

        // Filter mysql specific databases
        $ret = array_diff($ret, array('mysql', 'information_schema', 'performance_schema'));

        return $ret;
    }

    // ##########################################
    // General command building
    // ##########################################

    /**
     * Local Commander Builder Factory
     *
     * @param string $alias   Docker alias
     * @param string $command Command
     * @param array $args     Arguments
     * @return CommandBuilder|DockerExecCommandBuilder
     */
    protected function localDockerCommandBuilderFactory($dockerAlias, $command, $args=[])
    {
        if ($this->getLocalDockerContainer($dockerAlias)) {
            $command = new DockerExecCommandBuilder($command, $args);
            $command->setDockerContainer($this->getLocalDockerContainer($dockerAlias));
        } else {
            $command = new CommandBuilder($command, $args);
        }

        return $command;
    }

    // ##########################################
    // MySQL command building
    // ##########################################

    /**
     * Fetch docker mysql root password from environment variable
     *
     * @param $container
     * @return null|string
     */
    protected function getDockerMysqlRootPassword($container)
    {
        return DockerUtility::getDockerContainerEnv($container, 'MYSQL_ROOT_PASSWORD');
    }

    /**
     * Create mysql CommandBuilder (local or docker)
     *
     *
     * @param $args...
     * @return CommandBuilder|DockerExecCommandBuilder
     */
    protected function createMysqlCommand($args)
    {
        if ($this->getLocalDockerContainer(\CliTools\Console\Command\AbstractDockerCommand::DOCKER_ALIAS_MYSQL )) {
            $command = $this->createDockerMysqlCommand($this->getLocalDockerContainer(\CliTools\Console\Command\AbstractDockerCommand::DOCKER_ALIAS_MYSQL ), func_get_args());
        } else {
            $command = $this->createLocalMysqlCommand(func_get_args());
        }

        return $command;
    }

    /**
     * Create local mysql command
     *
     * @param array $args
     * @return CommandBuilder
     */
    protected function createLocalMysqlCommand($args)
    {
        $dbHostname = DatabaseConnection::getDbHostname();
        $dbPort     = DatabaseConnection::getDbPort();
        $dbUser     = DatabaseConnection::getDbUsername();
        $dbPassword = DatabaseConnection::getDbPassword();

        $command = new CommandBuilder('mysql', ['-N', '-B']);

        if (!empty($dbUser)) {
            $command->addArgumentTemplate('--user=%s', $dbUser);
        }

        if (!empty($dbHostname)) {
            $command->addArgumentTemplate('--host=%s', $dbHostname);
        }

        if (!empty($dbPort)) {
            $command->addArgumentTemplate('--port=%s', $dbPort);
        }

        $command->addArgumentList($args);

        if (!empty($dbPassword)) {
            $command->addEnvironmentVar('MYSQL_PWD', $dbPassword);
        }

        return $command;
    }

    /**
     * Create dockerized mysql command (using docker exec)
     *
     * @param array $args
     * @return CommandBuilder
     */
    protected function createDockerMysqlCommand($container, $args)
    {
        $command = new DockerExecCommandBuilder('mysql', ['-N', '-B']);
        $command->setDockerContainer($container);
        $command->addArgumentList($args);
        $command->addEnvironmentVar('MYSQL_PWD', DatabaseConnection::getDbPassword());
        return $command;
    }

    // ##########################################
    // MySQL command building
    // ##########################################

    /**
     * Create mysqldump CommandBuilder (local or docker)
     *
     *
     * @param $args...
     * @return CommandBuilder|DockerExecCommandBuilder
     */
    protected function createMysqldumpCommand($args)
    {
        if ($this->getLocalDockerContainer(\CliTools\Console\Command\AbstractDockerCommand::DOCKER_ALIAS_MYSQL )) {
            $command = $this->createDockerMysqldumpCommand($this->getLocalDockerContainer(\CliTools\Console\Command\AbstractDockerCommand::DOCKER_ALIAS_MYSQL ), func_get_args());
        } else {
            $command = $this->createLocalMysqldumpCommand(func_get_args());
        }

        return $command;
    }

    /**
     * Create local mysqldump command
     *
     * @param array $args
     * @return CommandBuilder
     */
    protected function createLocalMysqldumpCommand($args)
    {
        $dbHostname = DatabaseConnection::getDbHostname();
        $dbPort     = DatabaseConnection::getDbPort();
        $dbUser     = DatabaseConnection::getDbUsername();
        $dbPassword = DatabaseConnection::getDbPassword();

        $command = new CommandBuilder('mysqldump', ['--single-transaction']);

        if (!empty($dbUser)) {
            $command->addArgumentTemplate('--user=%s', $dbUser);
        }

        if (!empty($dbHostname)) {
            $command->addArgumentTemplate('--host=%s', $dbHostname);
        }

        if (!empty($dbPort)) {
            $command->addArgumentTemplate('--port=%s', $dbPort);
        }

        if (!empty($dbPassword)) {
            $command->addEnvironmentVar('MYSQL_PWD', $dbPassword);
        }

        $command->addArgumentList($args);

        return $command;
    }

    /**
     * Create dockerized mysqldump command (using docker exec)
     *
     * @param array $args
     * @return CommandBuilder
     */
    protected function createDockerMysqldumpCommand($container, $args)
    {
        $command = new DockerExecCommandBuilder('mysqldump', ['--single-transaction']);
        $command->setDockerContainer($container);
        $command->addArgumentList($args);
        $command->addEnvironmentVar('MYSQL_PWD', DatabaseConnection::getDbPassword());
        return $command;
    }
}
