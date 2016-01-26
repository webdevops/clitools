<?php

namespace CliTools\Utility;

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

use CliTools\Shell\CommandBuilder\CommandBuilder;
use CliTools\Shell\Executor;

class DockerUtility
{

    /**
     * Parse docker configuration (from docker inspect)
     *
     * @param string $container Name of docker container
     *
     * @return stdClass|null
     */
    public static function getDockerConfiguration($container)
    {

        // Build command
        $command = new CommandBuilder('docker', 'inspect %s', array($container));

        // execute
        $executor = new Executor($command);
        $executor->execute();
        $output = $executor->getOutputString();

        // Parse
        $conf = json_decode($output);

        if (!empty($conf)) {
            $conf = reset($conf);

            // Parse env
            if (!empty($conf->Config->Env)) {
                $envList = array();
                foreach ($conf->Config->Env as $value) {
                    list($envName, $envValue) = explode('=', $value, 2);
                    $envList[$envName] = $envValue;
                }

                $conf->Config->Env = $envList;
            }

            return $conf;
        }

        return null;
    }

    /**
     * Search docker-compose.yml recursive
     *
     * @param  NULL|string $path Docker path
     *
     * @return bool|string
     */
    public static function searchDockerDirectoryRecursive($path = null)
    {
        return UnixUtility::findFileInDirectortyTree('docker-compose.yml', $path);
    }

    /**
     * Check if current working directory is a docker instance directory
     *
     * @param  NULL|string $path Docker path
     *
     * @return bool
     */
    public static function isDockerDirectory($path = null)
    {
        if ($path === null) {
            $path = getcwd();
        }

        $dockerFileList = array(
            'docker-compose.yml'
        );

        foreach ($dockerFileList as $dockerFile) {
            $filePath = $path . '/' . $dockerFile;

            if (file_exists($filePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get docker instance prefix
     *
     * @param  NULL|string $path Docker path
     *
     * @return mixed|string
     */
    public static function getDockerInstancePrefix($path = null)
    {
        if ($path === null) {
            $path = getcwd();
        }

        $ret = strtolower(basename($path));

        $ret = preg_replace('/[^a-z0-9]/', '', $ret);

        return $ret;
    }

    /**
     * Get docker instance name
     *
     * @param  string      $containerName   Container name
     * @param  int         $containerNumber Container number
     * @param  NULL|string $path            Docker path
     *
     * @return string
     */
    public static function getDockerInstanceName($containerName, $containerNumber = 1, $path = null)
    {
        $dockerName = array(
            \CliTools\Utility\DockerUtility::getDockerInstancePrefix($path),
            (string)$containerName,
            (int)$containerNumber,
        );

        return implode('_', $dockerName);
    }
}
