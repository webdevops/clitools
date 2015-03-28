<?php

namespace CliTools\Utility;

/**
 * CliTools Command
 * Copyright (C) 2014 Markus Blaschke <markus@familie-blaschke.net>
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

class DockerUtility {

    /**
     * Check if current working directory is a docker instance directory
     *
     * @return bool
     */
    public static function isDockerDirectory() {
        $workDir = getcwd();

        $dockerFileList = array(
           'docker-compose.yml',
           'fig.yml',
        );

        foreach($dockerFileList as $dockerFile) {
            $filePath = $workDir . '/' . $dockerFile;

            if(file_exists($filePath)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Get docker instance prefix
     *
     * @return mixed|string
     */
    public static function getDockerInstancePrefix() {
        $workDir = getcwd();

        $ret = strtolower(basename($workDir));

        $ret = preg_replace('/[^a-z0-9]/', '', $ret);

        return $ret;
    }

    /**
     * Get docker instance name
     *
     * @param  string $containerName   Container name
     * @param  int    $containerNumber Container number
     * @return string
     */
    public static function getDockerInstanceName($containerName, $containerNumber = 1) {
        $dockerName = array(
            \CliTools\Utility\DockerUtility::getDockerInstancePrefix(),
            (string)$containerName,
            (int)$containerNumber,
        );

        return implode('_', $dockerName);
    }

}
