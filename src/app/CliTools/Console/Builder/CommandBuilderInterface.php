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

use CliTools\Console\Shell\Executor;

interface CommandBuilderInterface {

    /**
     * Construct
     */
    public function __construct();

    /**
     * Get command
     *
     * @return string
     */
    public function getCommand();

    /**
     * Set command
     *
     * @param string $command
     * @return $this
     */
    public function setCommand($command);

    /**
     * Clear arguments
     *
     * @return $this
     */
    public function clear();

    /**
     * Clear arguments
     *
     * @return $this
     */
    public function clearArguments();

    /**
     * Add argument separator
     *
     * @return $this
     */
    public function addArgumentSeparator();

    /**
     * Set argument from list
     *
     * @param  array $args Arguments
     * @return $this
     */
    public function setArgumentList(array $args);

    /**
     * Set arguments raw (unescaped)
     *
     * @param  string $arg... Argument
     * @return $this
     */
    public function addArgumentRaw($arg);

    /**
     * Set arguments
     *
     * @param  string $arg... Argument
     * @return $this
     */
    public function addArgument($arg);

    /**
     * Set argument with template
     *
     * @param string $arg     Argument sprintf
     * @param string $params  Argument parameters
     *
     * @return $this
     */
    public function addArgumentTemplate($arg, $params);

    /**
     * Set argument with template
     *
     * @param string $arg     Argument sprintf
     * @param array  $params  Argument parameters
     *
     * @return $this
     */
    public function addArgumentTemplateList($arg, array $params);

    /**
     * Add arguments list
     *
     * @param  array   $arg Argument
     * @param  boolean $escape Escape shell arguments
     * @return $this
     */
    public function addArgumentList(array $arg, $escape = true);

    /**
     * Get arguments list
     *
     * @return array
     */
    public function getArgumentList();

    /**
     * Get output redirect
     *
     * @return null|string
     */
    public function getOutputRedirect();

    /**
     * Set output (stdout and/or stderr) redirection
     *
     * @param null|string $outputRedirect
     * @return $this
     */
    public function setOutputRedirect($outputRedirect = null);


    /**
     * Redirect command stdout output to file
     *
     * @param string $filename Filename
     * @return $this
     */
    public function setOutputRedirectToFile($filename);

    /**
     * Parse command and attributs from exec line
     *
     * WARNING: Not safe!
     *
     * @param  string $str Command string
     * @return $this
     */
    public function parse($str);

    /**
     * Append another command builder
     *
     * @param CommandBuilderInterface $command  Command builder
     * @param boolean                 $inline   Add command as inline string (one big parameter)
     *
     * @return $this
     */
    public function append(CommandBuilderInterface $command, $inline = true);

    /**
     * Check if command is executeable
     *
     * @return bool
     */
    public function isExecuteable();

    /**
     *
     * Get pipe list
     *
     * @return array
     */
    public function getPipeList();

    /**
     * Set pipe list
     *
     * @param array $pipeList
     * @return $this
     */
    public function setPipeList(array $pipeList);


    /**
     * Add pipe command
     *
     * @param CommandBuilderInterface $command
     * @return $this
     */
    public function addPipeCommand(CommandBuilderInterface $command);

    /**
     * Build command string
     *
     * @return string
     * @throws \Exception
     */
    public function build();

    /**
     * Get executor
     *
     * @return Executor
     */
    public function getExecutor();

    /**
     * Set executor
     *
     * @param Executor $executor
     */
    public function setExecutor(Executor $executor);

    /**
     * Execute command
     *
     * @return Executor
     */
    public function execute();

    /**
     * Execute command
     *
     * @return Executor
     */
    public function executeInteractive();

}
