<?php

namespace CliTools\Console;

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

use CliTools\Database\DatabaseConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\ArgvInput;

class Application extends \Symfony\Component\Console\Application {

    /**
     * Configuration
     *
     * @var array
     */
    protected $config = array(
        'config'   => array(),
        'commands' => array(
            'class'  => array(),
            'ignore' => array(),
        )
    );

    /**
     * Tear down funcs
     *
     * @var array
     */
    protected $tearDownFuncList = array();

    /**
     * Load config
     *
     * @param string $file Config file (.ini)
     */
    public function loadConfig($file) {
        if (is_readable($file)) {
            $parsedConfig = parse_ini_file($file, true);
            $this->config = array_replace_recursive($this->config, $parsedConfig);
        }
    }

    /**
     * Get config value
     *
     * @param  string $area         Area
     * @param  string $confKey      Config Key
     * @param  null   $defaultValue Default value
     *
     * @return null
     */
    public function getConfigValue($area, $confKey, $defaultValue = null) {
        $ret = $defaultValue;

        if (isset($this->config[$area][$confKey])) {
            $ret = $this->config[$area][$confKey];
        }

        return $ret;
    }

    /**
     * Initialize
     */
    public function initialize() {
        $this->initializeChecks();
        $this->initializeConfiguration();
        $this->initializePosixTrap();

        define('CLITOOLS_COMMAND_CLI', $_SERVER['argv'][0]);
    }

    /**
     * Register tear down callback
     *
     * @param callable $func
     */
    public function registerTearDown(callable $func) {
        $this->tearDownFuncList[] = $func;
    }

    /**
     * Call teardown callbacks
     */
    public function callTearDown() {
        foreach ($this->tearDownFuncList as $func) {
            call_user_func($func);
        }
        $this->tearDownFuncList = array();
    }

    /**
     * Runs the current application.
     *
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return int 0 if everything went fine, or an error code
     * @throws \Exception
     */
    public function doRun(InputInterface $input, OutputInterface $output) {
        $ret = 0;

        try {
            $name = $this->getCommandName($input);

            if (!empty($name)) {
                /** @var \CliTools\Console\Command\AbstractCommand $command */
                $command = $this->find($name);
            }

            if (!empty($command) && $command instanceof \CliTools\Console\Filter\AnyParameterFilterInterface) {
                $argCount = $command->getDefinition()->getArgumentRequiredCount();

                $argvFiltered = array_splice($_SERVER['argv'], 0, 2 + $argCount);

                $input = new ArgvInput($argvFiltered);
                $this->configureIO($input, $output);

                $ret = parent::doRun($input, $output);
            } else {
                $ret = parent::doRun($input, $output);
            }
        } catch (\Exception $e) {
            $this->callTearDown();
            throw $e;
        }

        $this->callTearDown();

        return $ret;
    }

    /**
     * Initialize POSIX trap
     */
    protected function initializePosixTrap() {
        declare(ticks = 1);

        $signalHandler = function ($signal) {
            $this->callTearDown();

            // Prevent terminal messup
            echo "\n";
        };


        pcntl_signal(SIGTERM, $signalHandler);
        pcntl_signal(SIGINT, $signalHandler);
    }

    /**
     * PHP Checks
     */
    protected function initializeChecks() {
        if (!function_exists('pcntl_signal')) {
            echo ' [ERROR] PHP-Module pcnt not loaded';
            exit(1);
        }
    }

    /**
     * Initialize configuration
     */
    protected function initializeConfiguration() {
        $isRunningAsRoot = $this->isRunningAsRoot();

        //#########################
        // Database connection
        //#########################
        if (!empty($this->config['db'])) {
            $dsn      = null;
            $username = null;
            $password = null;

            if (!empty($this->config['db']['dsn'])) {
                $dsn = $this->config['db']['dsn'];
            }

            if (!empty($this->config['db']['username'])) {
                $username = $this->config['db']['username'];
            }

            if (!empty($this->config['db']['password'])) {
                $password = $this->config['db']['password'];
            }

            DatabaseConnection::setDsn($dsn, $username, $password);
        }

        //#########################
        // Commands
        //#########################
        if (!empty($this->config['commands']['class'])) {
            // Load list
            foreach ($this->config['commands']['class'] as $class) {

                // check ignore
                if (in_array($class, $this->config['commands']['ignore'])) {
                    continue;
                }

                if (class_exists($class)) {

                    // check OnlyRoot filter
                    if (!$isRunningAsRoot && is_subclass_of($class, '\CliTools\Console\Filter\OnlyRootFilterInterface')
                    ) {
                        // class only useable for root
                        continue;
                    }

                    $this->add(new $class);
                }
            }
        }
    }

    /**
     * Check if application is running as root
     *
     * @return bool
     */
    public function isRunningAsRoot() {
        $currentUid = (int)posix_getuid();

        return $currentUid === 0;
    }
}
