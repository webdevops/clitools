<?php

namespace CliTools\Shell\CommandBuilder;

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

use CliTools\Shell\Executor;

class AbstractCommandBuilder implements CommandBuilderInterface
{

    // ##########################################
    // Constants
    // ##########################################

    /**
     * Redirect STDOUT and STDERR to /dev/null (no output)
     */
    const OUTPUT_REDIRECT_NULL = ' &> /dev/null';

    /**
     * Redirect STDERR to STDOUT
     */
    const OUTPUT_REDIRECT_ALL_STDOUT = ' 2>&1';

    /**
     * Redirect STDERR to /dev/null (no error output)
     */
    const OUTPUT_REDIRECT_NO_STDERR = ' 2> /dev/null';

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
    protected $outputRedirect;

    /**
     * Command pipe
     *
     * @var array
     */
    protected $pipeList = array();

    /**
     * Executor
     *
     * @var null|Executor
     */
    protected $executor;

    /**
     * Environment list
     *
     * @var array
     */
    protected $envList = array();

    // ##########################################
    // Methods
    // ##########################################

    /**
     * Constructor
     *
     * @param null|string       $command   Command
     * @param null|string|array $args      Arguments
     * @param null|array        $argParams Argument params (sprintf)
     */
    public function __construct($command = null, $args = null, $argParams = null)
    {
        $this->initialize();

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
     * Initalized command
     */
    protected function initialize()
    {
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Set command
     *
     * @param string $command
     *
     * @return $this
     */
    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Clear arguments
     *
     * @return $this
     */
    public function clear()
    {
        $this->command        = null;
        $this->argumentList   = array();
        $this->outputRedirect = null;
        $this->pipeList       = array();
        $this->envList        = array();

        return $this;
    }

    /**
     * Clear arguments
     *
     * @return $this
     */
    public function clearArguments()
    {
        $this->argumentList = array();

        return $this;
    }

    /**
     * Add argument separator
     *
     * @return $this
     */
    public function addArgumentSeparator()
    {
        $this->argumentList[] = '--';

        return $this;
    }

    /**
     * Set argument from list
     *
     * @param  array $args Arguments
     *
     * @return $this
     */
    public function setArgumentList(array $args)
    {
        $this->clearArguments();
        $this->appendArgumentsToList($args);

        return $this;
    }

    /**
     * Set arguments raw (unescaped)
     *
     * @param  string $arg ... Argument
     *
     * @return $this
     */
    public function addArgumentRaw($arg)
    {
        return $this->addArgumentList(func_get_args(), false);
    }

    /**
     * Set arguments list raw (unescaped)
     *
     * @param  array $arg ... Argument
     *
     * @return $this
     */
    public function addArgumentListRaw($arg)
    {
        return $this->addArgumentList($arg, false);
    }

    /**
     * Set arguments
     *
     * @param  string $arg ... Argument
     *
     * @return $this
     */
    public function addArgument($arg)
    {
        return $this->addArgumentList(func_get_args());
    }

    /**
     * Add argument with template
     *
     * @param string $arg    Argument sprintf
     * @param string $params ...  Argument parameters
     *
     * @return $this
     */
    public function addArgumentTemplate($arg, $params)
    {
        $funcArgs = func_get_args();
        array_shift($funcArgs);

        return $this->addArgumentTemplateList($arg, $funcArgs);
    }

    /**
     * Add argument with template multiple times
     *
     * @param string $arg       Argument sprintf
     * @param array  $paramList Argument parameters
     *
     * @return $this
     */
    public function addArgumentTemplateMultiple($arg, $paramList)
    {
        foreach ($paramList as $param) {
            $this->addArgumentTemplate($arg, $param);
        }

        return $this;
    }

    /**
     * Set argument with template
     *
     * @param string $arg    Argument sprintf
     * @param array  $params Argument parameters
     *
     * @return $this
     */
    public function addArgumentTemplateList($arg, array $params)
    {
        $this->validateArgumentValue($arg);

        $params               = array_map('escapeshellarg', $params);
        $this->argumentList[] = vsprintf($arg, $params);

        return $this;
    }

    /**
     * Add arguments list
     *
     * @param  array   $arg    Argument
     * @param  boolean $escape Escape shell arguments
     *
     * @return $this
     */
    public function addArgumentList(array $arg, $escape = true)
    {
        $this->appendArgumentsToList($arg, $escape);

        return $this;
    }

    /**
     * Append one argument to list
     *
     * @param array   $arg    Arguments
     * @param boolean $escape Enable argument escaping
     *
     * @return $this
     */
    protected function appendArgumentToList($arg, $escape = true)
    {
        $this->validateArgumentValue($arg);

        if ($escape) {
            $arg = escapeshellarg($arg);
        }

        $this->argumentList[] = $arg;
    }

    /**
     * Append multiple arguments to list
     *
     * @param array   $args   Arguments
     * @param boolean $escape Enable argument escaping
     *
     * @return $this
     */
    protected function appendArgumentsToList($args, $escape = true)
    {
        // Validate each argument value
        array_walk($args, array($this, 'validateArgumentValue'));

        if ($escape) {
            $args = array_map('escapeshellarg', $args);
        }

        $this->argumentList = array_merge($this->argumentList, $args);
    }

    /**
     * Get arguments list
     *
     * @return array
     */
    public function getArgumentList()
    {
        return $this->argumentList;
    }

    /**
     * Get output redirect
     *
     * @return null|string
     */
    public function getOutputRedirect()
    {
        return $this->outputRedirect;
    }

    /**
     * Set output (stdout and/or stderr) redirection
     *
     * @param null|string $outputRedirect
     *
     * @return $this
     */
    public function setOutputRedirect($outputRedirect = null)
    {
        $this->outputRedirect = $outputRedirect;

        return $this;
    }


    /**
     * Redirect command stdout output to file
     *
     * @param string $filename Filename
     *
     * @return $this
     */
    public function setOutputRedirectToFile($filename)
    {
        $this->outputRedirect = '> ' . escapeshellarg($filename);

        return $this;
    }

    /**
     * Clear output redirect
     *
     * @return $this
     */
    public function clearOutputRedirect()
    {
        $this->outputRedirect = null;

        return $this;
    }

    /**
     * Parse command and attributs from exec line
     *
     * WARNING: Not safe!
     *
     * @param  string $str Command string
     *
     * @return $this
     */
    public function parse($str)
    {
        $parsedCmd = explode(' ', $str, 2);

        // Check required command
        if (empty($parsedCmd[0])) {
            throw new \RuntimeException('Command is empty');
        }

        // Set command (first value)
        $this->setCommand($parsedCmd[0]);

        // Set arguments (second values)
        if (!empty($parsedCmd[1])) {
            $this->addArgumentRaw($parsedCmd[1]);
        }

        return $this;
    }


    /**
     * Append another command builder
     *
     * @param CommandBuilderInterface $command Command builder
     * @param boolean                 $inline  Add command as inline string (one big parameter)
     *
     * @return $this
     */
    public function append(CommandBuilderInterface $command, $inline = true)
    {

        // Check if sub command is executeable
        if (!$command->isExecuteable()) {
            throw new \RuntimeException('Sub command "' . $command->getCommand() . '" is not executable or available');
        }

        if ($inline) {
            // Inline, one big parameter
            $this->addArgument($command->build());
        } else {
            $this->addArgument($command->command);
            $this->argumentList = array_merge($this->argumentList, $command->argumentList);
        }

        return $this;
    }

    /**
     * Check if command is executeable
     *
     * @return bool
     */
    public function isExecuteable()
    {
        // Command must be set
        if (empty($this->command)) {
            return false;
        }

        // Only check command paths for local commands
        if (!($this instanceof RemoteCommandBuilder) && !\CliTools\Utility\UnixUtility::checkExecutable(
                $this->command
            )
        ) {
            return false;
        }

        return true;
    }

    /**
     *
     * Get pipe list
     *
     * @return array
     */
    public function getPipeList()
    {
        return $this->pipeList;
    }

    /**
     * Set pipe list
     *
     * @param array $pipeList
     *
     * @return $this
     */
    public function setPipeList(array $pipeList)
    {
        $this->pipeList = $pipeList;

        return $this;
    }

    /**
     * Clear pipe list
     *
     * @return $this
     */
    public function clearPipes()
    {
        $this->pipeList = array();

        return $this;
    }

    /**
     * Add environment variable
     *
     * @param string $name  Variable name
     * @param string $value Variable value
     *
     * @return $this
     */
    public function addEnvironmentVar($name, $value)
    {
        $this->envList[$name] = $value;

        return $this;
    }

    /**
     * Add pipe command
     *
     * @param CommandBuilderInterface $command
     *
     * @return $this
     */
    public function addPipeCommand(CommandBuilderInterface $command)
    {
        $this->pipeList[] = $command;

        return $this;
    }

    /**
     * Build command string
     *
     * @return string
     * @throws \Exception
     */
    public function build()
    {
        $ret = array();

        if (!$this->isExecuteable()) {
            throw new \RuntimeException(
                'Command "' . $this->getCommand() . '" is not executable or available, please install it'
            );
        }

        foreach ($this->envList as $envName => $envValue) {
            $ret[] = $envName . '=' . escapeshellarg($envValue);
        }

        var_dump($this->envList);

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
     * Get executor
     *
     * @return Executor
     */
    public function getExecutor()
    {
        if ($this->executor === null) {
            $this->executor = new Executor($this);
        }

        return $this->executor;
    }

    /**
     * Set executor
     *
     * @param Executor $executor
     */
    public function setExecutor(Executor $executor)
    {
        $this->executor = $executor;
    }

    /**
     * Execute command
     *
     * @return Executor
     */
    public function execute()
    {
        return $this->getExecutor()
                    ->execute();
    }

    /**
     * Execute command
     *
     * @param array $opts Option array
     *
     * @return Executor
     */
    public function executeInteractive(array $opts = null)
    {
        return $this->getExecutor()
                    ->execInteractive($opts);
    }

    /**
     * Validate argument value
     *
     * @param mixed $value Value
     *
     * @throws \RuntimeException
     */
    protected function validateArgumentValue($value)
    {
        if (strlen($value) === 0) {
            throw new \RuntimeException('Argument value cannot be empty');
        }
    }

    // ##########################################
    // Magic methods
    // ##########################################

    /**
     * Clone command
     */
    public function __clone()
    {
        if (!empty($this->executor)) {
            $this->executor = clone $this->executor;
        }
    }

    /**
     * To string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->build();
    }

}
