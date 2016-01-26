<?php

namespace CliTools\Exception;

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

use CliTools\Shell\CommandBuilder\CommandBuilderInterface;

class CommandExecutionException extends \RuntimeException
{

    /**
     * Return code from cli command
     *
     * @var null|integer
     */
    protected $returnCode;

    /**
     * Return code from cli command
     *
     * @var null|CommandBuilderInterface
     */
    protected $command;

    /**
     * Get return code from cli command
     *
     * @return integer
     */
    public function getReturnCode()
    {
        return $this->returnCode;
    }

    /**
     * Set return code from cli command
     *
     * @param integer $returnCode
     */
    public function setReturnCode($returnCode)
    {
        $this->returnCode = $returnCode;
    }

    /**
     * Get command
     *
     * @return CommandBuilderInterface|null
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Set command
     *
     * @param CommandBuilderInterface $command
     */
    public function setCommand(CommandBuilderInterface $command)
    {
        $this->command = $command;
    }


}
