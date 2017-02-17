<?php

namespace CliTools\Console\Command\Mysql;

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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CliTools\Shell\CommandBuilder\DockerExecCommandBuilder;
use CliTools\Utility\DockerUtility;

abstract class AbstractCommand extends \CliTools\Console\Command\AbstractCommand
{

    /**
     * Docker container
     */
    protected $dockerContainer;

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->addOption(
                'host',
                null,
                InputOption::VALUE_REQUIRED,
                'MySQL host'
            )
             ->addOption(
                 'port',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'MySQL port'
             )
            ->addOption(
                'docker',
                null,
                InputOption::VALUE_REQUIRED,
                'Docker container id'
            )
            ->addOption(
                'docker-compose',
                null,
                InputOption::VALUE_REQUIRED,
                'Docker-Compose container name'
            )
             ->addOption(
                 'user',
                 'u',
                 InputOption::VALUE_REQUIRED,
                 'MySQL user'
             )
             ->addOption(
                 'password',
                 'p',
                 InputOption::VALUE_REQUIRED,
                 'MySQL host'
             );
    }

    /**
     * Initializes the command just after the input has been validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $dsn      = null;
        $user     = null;
        $password = null;
        $host     = DatabaseConnection::getDbHostname();
        $port     = DatabaseConnection::getDbPort();

        // init docker environment
        if ($this->input->getOption('docker-compose')) {
            $this->dockerContainer = DockerUtility::lookupDockerComposeContainerId($this->input->getOption('docker-compose'));
            $password = $this->getDockerMysqlRootPassword($this->dockerContainer);
        } elseif ($this->input->getOption('docker')) {
            $this->dockerContainer = $this->input->getOption('docker');
            $password = $this->getDockerMysqlRootPassword($this->dockerContainer);
        }

        // host
        if ($this->input->hasOption('host') && $this->input->getOption('host')) {
            $host = $this->input->getOption('host');
            $dsn  = false;
        }

        // port
        if ($this->input->hasOption('port') && $this->input->getOption('port')) {
            $port = $this->input->getOption('port');
            $dsn  = false;
        }

        // rebuild dsn
        if ($dsn === false) {
            $dsn = 'mysql:host=' . urlencode($host) . ';port=' . (int)$port;
        }

        // user
        if ($this->input->hasOption('user') && $this->input->getOption('user')) {
            $user = $this->input->getOption('user');
        }

        // password
        if ($this->input->hasOption('password') && $this->input->getOption('password')) {
            $password = $this->input->getOption('password');
        }

        if ($dsn !== null || $user !== null || $password !== null) {
            DatabaseConnection::setDsn($dsn, $user, $password);
        }
    }

    protected function mysqlQuote($value) {
        return '\'' . addslashes($value) . '\'';
    }

    protected function execSqlCommand($sql)
    {
        return $this->createMysqlCommand('-e', $sql)->execute()->getOutput();
    }

    protected function mysqlTableList($database)
    {
        $sql = sprintf(
            'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s',
            $this->mysqlQuote($database)
        );
        return $this->execSqlCommand($sql);
    }


    protected function mysqlDatabaseList()
    {
        $sql = 'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA';
        $ret = $this->execSqlCommand($sql);

        // Filter mysql specific databases
        $ret = array_diff($ret, array('mysql', 'information_schema', 'performance_schema'));

        return $ret;
    }

    // ##########################################
    // MySQL command building
    // ##########################################

    protected function getDockerMysqlRootPassword($container)
    {
        $command = new DockerExecCommandBuilder('printf');
        $command->addArgumentRaw('$MYSQL_ROOT_PASSWORD');
        $command->setDockerContainer($container);
        return $command->execute()->getOutputString();
    }

    protected function createMysqlCommand($args)
    {
        if ($this->input->getOption('docker-compose') || $this->input->getOption('docker')) {
            $this->output->writeln('<info>Using Docker container</info>');
            $command = $this->createDockerMysqlCommand($this->dockerContainer, func_get_args());
        } else {
            $this->output->writeln('<info>Using local mysql command</info>');
            $command = $this->createLocalMysqlCommand(func_get_args());
        }

        return $command;
    }

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

    protected function createDockerMysqlCommand($container, $args)
    {
        $command = new DockerExecCommandBuilder('mysql', ['-N', '-B', '-e']);
        $command->setDockerContainer($container);
        $command->setArgumentList($args);
        $command->addEnvironmentVar('MYSQL_PWD', DatabaseConnection::getDbPassword());
        return $command;
    }


    // ##########################################
    // MySQL command building
    // ##########################################

    protected function createMysqldumpCommand($args)
    {
        if ($this->input->getOption('docker-compose') || $this->input->getOption('docker')) {
            $this->output->writeln('<info>Using Docker container</info>');
            $command = $this->createDockerMysqldumpCommand($this->dockerContainer, func_get_args());
        } else {
            $this->output->writeln('<info>Using local mysqldump command</info>');
            $command = $this->createLocalMysqldumpCommand(func_get_args());
        }

        return $command;
    }

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

    protected function createDockerMysqldumpCommand($container, $args)
    {
        $command = new DockerExecCommandBuilder('mysqldump', ['--single-transaction']);
        $command->setDockerContainer($container);
        $command->addArgumentList($args);
        $command->addEnvironmentVar('MYSQL_PWD', DatabaseConnection::getDbPassword());
        return $command;
    }
}
