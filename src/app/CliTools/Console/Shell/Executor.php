<?php

namespace CliTools\Console\Shell;

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

use CliTools\Exception\CommandExecutionException;
use CliTools\Console\Builder\CommandBuilder;
use CliTools\Utility\ConsoleUtility;

class Executor {

    // ##########################################
    // Constants
    // ##########################################


    // ##########################################
    // Attributs
    // ##########################################

    /**
     * Command
     *
     * @var CommandBuilder
     */
    protected $command;

    /**
     * Return code
     *
     * @var null|integer
     */
    protected $returnCode;

    /**
     * Output
     *
     * @var null|array
     */
    protected $output;

    /**
     * Strict mode (will throw exception if exec fails)
     *
     * @var bool
     */
    protected $strictMode = true;

    // ##########################################
    // Methods
    // ##########################################

    /**
     * Constructor
     *
     * @param null|CommandBuilder $command   Command for execution
     */
    public function __construct(CommandBuilder $command = null) {
        if ($command !== null) {
            $this->command = $command;
        }
    }

    /**
     * Get command
     *
     * @return CommandBuilder
     */
    public function getCommand() {
        return $this->command;
    }

    /**
     * Set command
     *
     * @param CommandBuilder $command
     * @return $this
     */
    public function setCommand($command) {
        $this->command = $command;
        return $this;
    }

    /**
     * Get return code
     *
     * @return int|null
     */
    public function getReturnCode() {
        return $this->returnCode;
    }

    /**
     * Get output
     *
     * @return array|null
     */
    public function getOutput() {
        return $this->output;
    }

    /**
     * Get output as string
     *
     * @return string|null
     */
    public function getOutputString() {
        $ret = null;

        if ($this->output !== null) {
            $ret = trim(implode("\n", $this->output));
        }

        return $ret;
    }

    /**
     * Check if strict mode is enabled
     *
     * @return boolean
     */
    public function isStrictMode() {
        return (bool)$this->strictMode;
    }

    /**
     * Set strict mode
     *
     * @return $this
     * @param boolean $strictMode
     */
    public function setStrictMode($strictMode) {
        $this->strictMode = (bool)$strictMode;
        return $this;
    }


    /**
     * Execute command
     *
     * @return $this
     * @throws \Exception
     */
    public function execute() {
        $this->checkCommand();

        ConsoleUtility::verboseWriteln('EXEC::STD', $this->command->build());

        exec($this->command->build(), $this->output, $this->returnCode);

        if ($this->strictMode && $this->returnCode !== 0) {
            throw $this->generateException('Process ' . $this->command->getCommand() . ' did not finished successfully');
        }

        return $this;
    }

    /**
     * Execute interactive
     *
     * @return $this
     * @throws \Exception
     */
    public function execInteractive() {
        $this->checkCommand();

        ConsoleUtility::verboseWriteln('EXEC::INTERACTIVE', $this->command->build());

        $descriptorSpec = array(
            0 => array('file', 'php://stdin',  'r'),  // stdin is a file that the child will read from
            1 => array('file', 'php://stdout', 'w'),  // stdout is a file that the child will write to
            2 => array('file', 'php://stderr', 'w')   // stderr is a file that the child will write to
        );

        $process = proc_open($this->command->build(), $descriptorSpec, $pipes);

        if (is_resource($process)) {
            $execStatus = proc_close($process);
            $this->returnCode = pcntl_wexitstatus($execStatus);

            if ($this->strictMode && $this->returnCode !== 0) {
                throw $this->generateException('Process ' . $this->command->getCommand() . ' did not finished successfully');
            }
        } else {
            throw $this->generateException('Process ' . $this->command->getCommand() . ' could not be started');
        }

        return $this;
    }

    /**
     * Check command
     *
     * @return $this
     * @throws CommandExecutionException
     */
    protected function checkCommand() {
        if ($this->command === null) {
            throw $this->generateException('Commmand is not set');
        }

        if (!$this->command->isExecuteable()) {
            throw $this->generateException('Commmand is not executable');
        }

        return $this;
    }

    /**
     * Generate exception
     *
     * @param string $msg Exception message
     *
     * @return CommandExecutionException
     */
    protected function generateException($msg) {
        $e = new CommandExecutionException($msg);

        if ($this->returnCode !== null) {
            $e->setReturnCode($this->returnCode);
        }

        if ($this->command !== null) {
            $e->setCommand($this->command);
        }

        return $e;
    }
}
