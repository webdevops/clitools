<?php

namespace CliTools\Console\Builder;

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

class CommandBuilder {

    // ##########################################
    // Constants
    // ##########################################


    const OUTPUT_REDIRECT_NULL       = ' &> /dev/null';
    const OUTPUT_REDIRECT_ALL_STDOUT = ' 2>&1';

    // ##########################################
    // Attributs
    // ##########################################

    /**
     * Command
     *
     * @var string
     */
    protected $command;

    /**
     * Argument list
     *
     * @var array
     */
    protected $argumentList = array();

    /**
     * Output redirection
     *
     * @var null|string
     */
    protected $outputRedirect = null;

    /**
     * Command pipe
     *
     * @var array
     */
    protected $pipeList = array();

    // ##########################################
    // Methods
    // ##########################################

    /**
     * Constructor
     *
     * @param null|string        $command   Command
     * @param null|string|array  $args      Arguments
     * @param null|array         $argParams Argument params (sprintf)
     */
    public function __construct($command = null, $args = null, $argParams = null) {
        if ($command !== null) {
            $this->setCommand($command);
        }

        if ($args !== null) {

            if (is_array($args)) {
                $this->setArgumentList($args);
            } else {
                if (strpos($args, '%s') !== false) {
                    // sprintf string found
                    $this->addArgumentTemplateList($args, $argParams);
                } else {
                    // default param string
                    $this->addArgumentRaw($args);

                    if (!empty($argParams)) {
                        $this->addArgumentList($argParams);
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getCommand() {
        return $this->command;
    }

    /**
     * Set command
     *
     * @param string $command
     * @return $this
     */
    public function setCommand($command) {
        $this->command = $command;
        return $this;
    }

    /**
     * Clear arguments
     *
     * @return $this
     */
    public function clear() {
        $this->command        = null;
        $this->argumentList   = array();
        $this->outputRedirect = null;
        $this->pipeList       = array();
        return $this;
    }

    /**
     * Clear arguments
     *
     * @return $this
     */
    public function clearArguments() {
        $this->argumentList = array();
        return $this;
    }

    /**
     * Add argument separator
     *
     * @return $this
     */
    public function addArgumentSeparator() {
        $this->argumentList[] = '--';
        return $this;
    }

    /**
     * Set argument from list
     *
     * @param  array $args Arguments
     * @return $this
     */
    public function setArgumentList(array $args) {
        $this->argumentList = $args;
        return $this;
    }

    /**
     * Set arguments raw (unescaped)
     *
     * @param  string $arg... Argument
     * @return $this
     */
    public function addArgumentRaw($arg) {
        return $this->addArgumentList(func_get_args(), false);
    }

    /**
     * Set arguments
     *
     * @param  string $arg... Argument
     * @return $this
     */
    public function addArgument($arg) {
        return $this->addArgumentList(func_get_args());
    }

    /**
     * Set argument with template
     *
     * @param string $arg     Argument sprintf
     * @param string $params  Argument parameters
     *
     * @return $this
     */
    public function addArgumentTemplate($arg, $params) {
        $funcArgs = func_get_args();
        array_shift($funcArgs);

        return $this->addArgumentTemplateList($arg, $funcArgs);
    }

    /**
     * Set argument with template
     *
     * @param string $arg     Argument sprintf
     * @param array  $params  Argument parameters
     *
     * @return $this
     */
    public function addArgumentTemplateList($arg, array $params) {
        $params = array_map('escapeshellarg', $params);
        $this->argumentList[] = vsprintf($arg, $params);
        return $this;
    }

    /**
     * Add arguments list
     *
     * @param  array   $arg Argument
     * @param  boolean $escape Escape shell arguments
     * @return $this
     */
    public function addArgumentList(array $arg, $escape = true) {
        if ($escape) {
            $arg = array_map('escapeshellarg', $arg);
        }

        $this->argumentList = array_merge($this->argumentList, $arg);
        return $this;
    }


    /**
     * Get arguments list
     *
     * @return array
     */
    public function getArgumentList() {
        return $this->argumentList;
    }

    /**
     * Get output redirect
     *
     * @return null|string
     */
    public function getOutputRedirect() {
        return $this->outputRedirect;
    }

    /**
     * @param null|string $outputRedirect
     */
    public function setOutputRedirect($outputRedirect = null) {
        $this->outputRedirect = $outputRedirect;
    }

    /**
     * Parse command and attributs from exec line
     *
     * Not safe!
     *
     * @param  string $str Command string
     * @return $this
     */
    public function parse($str) {
        list($command, $attributs) = explode(' ', $str, 2);

        $this->setCommand($command);
        $this->setArgumentList(array($attributs), false);
        return $this;
    }


    /**
     * Append another command builder
     *
     * @param CommandBuilder $command  Command builder
     * @param boolean        $inline   Add command as inline string (one big parameter)
     *
     * @return $this
     */
    public function append(CommandBuilder $command, $inline = true) {
        // Check if sub command is executeable
        if (!$command->isExecuteable()) {
            throw new \RuntimeException('Subcommand is not executable');
        }

        if ($inline) {
            // Inline, one big parameter
            $this->addArgument($command->build());
        } else {
            // Append each as own argument
            $this->addArgument( $command->command );
            $this->argumentList = array_merge($this->argumentList, $command->argumentList);
        }

        return $this;
    }

    /**
     * Check if command is executeable
     *
     * @return bool
     */
    public function isExecuteable() {
        $ret = false;

        if (!empty($this->command)) {
            $ret = true;
        }

        return $ret;
    }

    /**
     *
     * Get pipe list
     *
     * @return array
     */
    public function getPipeList() {
        return $this->pipeList;
    }

    /**
     * Set pipe list
     *
     * @param array $pipeList
     */
    public function setPipeList(array $pipeList) {
        $this->pipeList = $pipeList;
    }


    /**
     * Add pipe command
     *
     * @param CommandBuilder $command
     */
    public function addPipeCommand(CommandBuilder $command) {
        $this->pipeList[] = $command;
    }

    /**
     * Build command string
     *
     * @return string
     * @throws \Exception
     */
    public function build() {
        $ret = array();

        if ($this->command === null) {
            throw new \Exception('Command can\'t be empty');
        }

        // Add command
        $ret[] = escapeshellcmd($this->command);

        // Add parameters
        $ret = array_merge($ret, $this->argumentList);

        $ret = implode(' ', $ret);

        // Output redirect
        if ($this->outputRedirect !== null) {
            $ret .= ' ' . $this->outputRedirect;
        }

        // Pipes
        foreach ($this->pipeList as $command) {
            if ($command instanceof CommandBuilder) {
                $ret .= ' | ' . $command->build();
            }
        }

        return $ret;
    }

    /**
     * To string
     *
     * @return string
     */
    public function __toString() {
        return $this->build();
    }

}
