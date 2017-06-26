<?php

namespace CliTools\Shell\CommandBuilder;

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

class DockerExecCommandBuilder extends CommandBuilder
{

    // ##########################################
    // Attributs
    // ##########################################

    /**
     * Docker container ID
     *
     * @var string
     */
    protected $dockerContainer;

    /**
     * Specifies if this is the internal docker command
     *
     * @var bool
     */
    protected $dockerInternalCommand = false;

    /**
     * @return string
     */
    public function getDockerContainer()
    {
        return $this->dockerContainer;
    }

    /**
     * @param string $dockerContainer
     * @return MysqlCommandBuilder
     */
    public function setDockerContainer($dockerContainer)
    {
        $this->dockerContainer = $dockerContainer;
        return $this;
    }

    // ##########################################
    // Methods
    // ##########################################


    /**
     * Build command string
     *
     * @return string
     * @throws \Exception
     */
    public function build()
    {
        if ($this->dockerInternalCommand) {
            return parent::build();
        }

        if (empty($this->dockerContainer)) {
            throw new \InvalidArgumentException('Docker container is missing for command builder');
        }

        $subCommand = clone $this;
        $subCommand->clearPipes();
        $subCommand->clearOutputRedirect();
        $subCommand->dockerInternalCommand = true;

        $dockerCommand = clone $this;
        $dockerCommand->dockerInternalCommand = true;
        $dockerCommand->clearEnvironment();
        $dockerCommand->setCommand('docker')
            ->setArgumentList(['exec', '-i', (string)$this->dockerContainer])
            ->addArgument('sh', '-c', $subCommand->build());

        return $dockerCommand->build();
    }

}
