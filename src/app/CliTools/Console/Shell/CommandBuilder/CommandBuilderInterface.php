<?php

namespace CliTools\Console\Shell\CommandBuilder;

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
     * @return string
     */
    public function getCommand();

    /**
     * Set command
     *
     * @param string $command
     *
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
     *
     * @return $this
     */
    public function setArgumentList(array $args);

    /**
     * Set arguments raw (unescaped)
     *
     * @param  string $arg ... Argument
     *
     * @return $this
     */
    public function addArgumentRaw($arg);

    /**
     * Set arguments list raw (unescaped)
     *
     * @param  array $arg ... Argument
     *
     * @return $this
     */
    public function addArgumentListRaw($arg);

    /**
     * Set arguments
     *
     * @param  string $arg ... Argument
     *
     * @return $this
     */
    public function addArgument($arg);

    /**
     * Add argument with template
     *
     * @param string $arg    Argument sprintf
     * @param string $params ...  Argument parameters
     *
     * @return $this
     */
    public function addArgumentTemplate($arg, $params);

    /**
     * Add argument with template multiple times
     *
     * @param string $arg       Argument sprintf
     * @param array  $paramList Argument parameters
     *
     * @return $this
     */
    public function addArgumentTemplateMultiple($arg, $paramList);

    /**
     * Set argument with template
     *
     * @param string $arg    Argument sprintf
     * @param array  $params Argument parameters
     *
     * @return $this
     */
    public function addArgumentTemplateList($arg, array $params);

    /**
     * Add arguments list
     *
     * @param  array   $arg    Argument
     * @param  boolean $escape Escape shell arguments
     *
     * @return $this
     */
    public function addArgumentList(array $arg, $escape = true);

    /**
     * Append one argument to list
     *
     * @param array   $arg    Arguments
     * @param boolean $escape Enable argument escaping
     *
     * @return $this
     */
    public function appendArgumentToList($arg, $escape = true);

    /**
     * Append multiple arguments to list
     *
     * @param array   $args   Arguments
     * @param boolean $escape Enable argument escaping
     *
     * @return $this
     */
    public function appendArgumentsToList($args, $escape = true);

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
     *
     * @return $this
     */
    public function setOutputRedirect($outputRedirect = null);

    /**
     * Redirect command stdout output to file
     *
     * @param string $filename Filename
     *
     * @return $this
     */
    public function setOutputRedirectToFile($filename);

    /**
     * Clear output redirect
     *
     * @return $this
     */
    public function clearOutputRedirect();

    /**
     * Parse command and attributs from exec line
     *
     * WARNING: Not safe!
     *
     * @param  string $str Command string
     *
     * @return $this
     */
    public function parse($str);

    /**
     * Append another command builder
     *
     * @param CommandBuilderInterface $command Command builder
     * @param boolean                 $inline  Add command as inline string (one big parameter)
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
     *
     * @return $this
     */
    public function setPipeList(array $pipeList);

    /**
     * Clear pipe list
     *
     * @return $this
     */
    public function clearPipes();

    /**
     * Add pipe command
     *
     * @param CommandBuilderInterface $command
     *
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
     * @return \CliTools\Console\Shell\Executor
     */
    public function getExecutor();

    /**
     * Set executor
     *
     * @param \CliTools\Console\Shell\Executor $executor
     */
    public function setExecutor(\CliTools\Console\Shell\Executor $executor);

    /**
     * Execute command
     *
     * @return \CliTools\Console\Shell\Executor
     */
    public function execute();

    /**
     * Execute command
     *
     * @return \CliTools\Console\Shell\Executor
     */
    public function executeInteractive();

    /**
     * Validate argument value
     *
     * @param mixed $value Value
     *
     * @throws \RuntimeException
     */
    public function validateArgumentValue($value);

    /**
     * To string
     *
     * @return string
     */
    public function __toString();

}
