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

class OutputCombineCommandBuilder extends AbstractCommandBuilder
{

    /**
     * Command list which should be combined
     *
     * @var array
     */
    protected $commandList = array();

    /**
     * Add command for combined output
     *
     * @param CommandBuilderInterface $command
     *
     * @return $this
     */
    public function addCommandForCombinedOutput(CommandBuilderInterface $command)
    {
        if ($command->isExecuteable()) {
            $this->commandList[] = $command;
        } else {
            throw new \RuntimeException('Command "' . $command->getCommand() . '" is not executable or available');
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
        $ret = true;

        if (empty($this->commandList)) {
            $ret = false;
        }

        return $ret;
    }

    /**
     * Clear arguments
     *
     * @return $this
     */
    public function clear()
    {
        $ret = parent::__clone();

        $this->commandList = array();

        return $ret;
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
            throw new \RuntimeException('Command is not executable or available');
        }

        // Generate list of combined commands
        $combinedList = array();
        foreach ($this->commandList as $command) {
            $combinedList[] = $command->build();
        }
        $ret = '( ' . implode(' ; ', $combinedList) . ' )';

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
     * Clone command
     */
    public function __clone()
    {
        parent::__clone();

        $commandList = array();
        foreach ($this->commandList as $command) {
            $commandList[] = clone $command;
        }
        $this->commandList = $commandList;
    }
}
