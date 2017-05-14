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

abstract class AbstractCommand extends Command
{

    const DOCKER_ALIAS_MYSQL = 'mysql';

    /**
     * Message list (will be shown at the end)
     *
     * @var array
     */
    protected $finishMessageList = array();

    /**
     * Input
     *
     * @var InputInterface
     */
    protected $input;

    /**
     * Input
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * Enable automatic terminal title
     *
     * @var bool
     */
    protected $automaticTerminalTitle = true;

    /**
     * Docker container
     */
    protected $dockerContainer;

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
        $this->input  = $input;
        $this->output = $output;

        ConsoleUtility::initialize($input, $output);

        if ($this->automaticTerminalTitle) {
            // Set default terminal title
            $this->setTerminalTitle(explode(':', $this->getName()));
        }
    }

    /**
     * Runs the command.
     *
     * The code to execute is either defined directly with the
     * setCode() method or by overriding the execute() method
     * in a sub-class.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int The command exit code
     *
     * @throws \Exception
     *
     * @see setCode()
     * @see execute()
     *
     * @api
     */
    public function run(InputInterface $input, OutputInterface $output)
    {

        try {
            $ret = parent::run($input, $output);
            $this->showFinishMessages();
        } catch (\Exception $e) {
            $this->showFinishMessages();
            throw $e;
        }

        return $ret;
    }


    /**
     * Get full parameter list
     *
     * @param integer $offset Parameter offset
     *
     * @return mixed
     */
    protected function getFullParameterList($offset = null)
    {
        $ret = $_SERVER['argv'];

        // remove requested offset
        if ($offset !== null) {
            $ret = array_splice($ret, $offset);
        }

        return $ret;
    }

    /**
     * Elevate process (exec sudo with same parameters)
     *
     * @param  InputInterface  $input  Input instance
     * @param  OutputInterface $output Output instance
     *
     * @return int|null|void
     */
    protected function elevateProcess(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getApplication()
                  ->isRunningAsRoot()
        ) {
            // Process is not running as root, trying to elevate to root
            $output->writeln('<comment>Elevating process using sudo...</comment>');

            try {
                $commandMyself = new FullSelfCommandBuilder();

                $commandSudo = new CommandBuilder('sudo');
                $commandSudo->append($commandMyself, false);
                $commandSudo->executeInteractive();
            } catch (\Exception $e) {
                // do not display exception here because it's a child process
            }
            throw new \CliTools\Exception\StopException(0);
        } else {
            // running as root
        }
    }

    /**
     * Show log, passthru multitail
     *
     * @param  array           $logList    List of log files
     * @param  InputInterface  $input      Input instance
     * @param  OutputInterface $output     Output instance
     * @param  string          $grep       Grep value
     * @param  array           $optionList Additional option list for multitail
     *
     * @return int|null|void
     * @throws \Exception
     */
    protected function showLog($logList, $input, $output, $grep = null, $optionList = null)
    {
        $this->elevateProcess($input, $output);

        // check if logfiles are accessable
        foreach ($logList as $log) {
            if (!is_readable($log)) {
                $output->writeln('<p-error>Can\'t read ' . $log . '</p-error>');

                return 1;
            }
        }

        $output->writeln('<p>Reading logfile with multitail</p>');

        $command = new CommandBuilder('multitail', '--follow-all');

        // Add grep
        if ($grep !== null) {
            $command->addArgumentTemplate('-E %s', $grep);
        }

        // Add log
        $command->addArgumentList($logList);
        $command->executeInteractive();

        return 0;
    }

    /**
     * Add message to finish list
     *
     * @param string $message Message
     */
    protected function addFinishMessage($message)
    {
        $this->output->writeln($message);
        $this->finishMessageList[] = $message;
    }

    /**
     * Show all finish messages
     */
    protected function showFinishMessages()
    {

        if (!empty($this->finishMessageList)) {
            $this->output->writeln('');
            $this->output->writeln('Replay finish message log:');

            foreach ($this->finishMessageList as $message) {
                $this->output->writeln('  - ' . $message);
            }
        }

        $this->finishMessageList = array();
    }

    /**
     * Gets the application instance for this command.
     *
     * @return \CliTools\Console\Application An Application instance
     *
     * @api
     */
    public function getApplication()
    {
        return parent::getApplication();
    }

    /**
     * Sets the terminal title of the command.
     *
     * This feature should be used only when creating a long process command,
     * like a daemon.
     *
     * PHP 5.5+ or the proctitle PECL library is required
     *
     * @param string $title The terminal title
     *
     * @return Command The current instance
     */
    public function setTerminalTitle($title)
    {
        // no title if stdout redirect
        if(!posix_isatty(STDOUT)) {
            return;
        }

        $args = func_get_args();

        $titleList = array();
        foreach ($args as $value) {
            if (is_array($value)) {
                $value = implode(' ', $value);
            }

            $titleList[] = trim($value);
        }

        $title = implode(' ', $titleList);
        $title = trim($title);

        $this->getApplication()
             ->setTerminalTitle($title);

        return $this;
    }

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
    protected function execSqlCommand($sql, $database = null)
    {
        $command = $this->createMysqlCommand('-e', $sql);

        if ($database !== null) {
            $command->addArgumentTemplate('-D %s', $database);
        }

        $command->execute()->getOutput();
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
