<?php

namespace CliTools\Builder;

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

    // ##########################################
    // Methods
    // ##########################################

    /**
     * Constructor
     *
     * @param null|string $command  Command
     * @param null|array  $args     Arguments
     */
    public function __construct($command = null, $args = null) {
        if ($command !== null) {
            $this->setCommand($command);
        }

        if ($args !== null) {
            $this->setArguments($args);
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
     * @return CommandBuilder
     */
    public function setCommand($command) {
        $this->command = $command;
        return $this;
    }

    /**
     * Clear arguments
     *
     * @return CommandBuilder
     */
    public function clear() {
        $this->command      = null;
        $this->argumentList = array();
        return $this;
    }

    /**
     * Clear arguments
     *
     * @return CommandBuilder
     */
    public function clearArguments() {
        $this->argumentList = array();
        return $this;
    }

    /**
     * Set argument from list
     *
     * @param  array $args Arguments
     * @return CommandBuilder
     */
    public function setArgumentList(array $args) {
        $this->argumentList = $args;
        return $this;
    }

    /**
     * Set arguments
     *
     * @param  string $arg Argument
     * @return CommandBuilder
     */
    public function addArgument($arg) {
        return $this->addArgumentList(func_get_args());
    }

    /**
     * Add arguments list
     *
     * @param  array $arg Argument
     * @return CommandBuilder
     */
    public function addArgumentList(array $arg) {
        $this->argumentList = array_merge($this->argumentList, $arg);
        return $this;
    }

    /**
     * Parse command and attributs from exec line
     *
     * Not safe!
     *
     * @param  string $str Command string
     * @return CommandBuilder
     */
    public function parse($str) {
        list($command, $attributs) = explode(' ', $str, 2);

        $this->setCommand($command);
        $this->setArgumentList($attributs);
        return $this;
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

        $ret[] = escapeshellcmd($this->command);

        $argumentList = array_map('escapeshellarg', $this->argumentList);
        $ret = array_merge($ret, $argumentList);

        $ret = implode(' ', $ret);

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
