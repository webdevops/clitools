<?php

namespace CliTools\Utility;

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

class DockerUtility {

    /**
     * Search docker-compose.yml recursive
     *
     * @param  NULL|string $path Docker path
     * @return bool|string
     */
    public static function searchDockerDirectoryRecursive($path = NULL) {
        $ret = FALSE;

        if ($path === NULL) {
            $path = getcwd();
        }


        if( !empty($path) && $path !== '/') {
            // Check if current path is docker directory
            if (self::isDockerDirectory($path)) {
                // Docker found
                $ret = $path;
            } else {
                // go up in directory
                $path .= '/../';
                $path = realpath($path);
                $ret = self::searchDockerDirectoryRecursive($path);
            }
        }

        return $ret;
    }

    /**
     * Check if current working directory is a docker instance directory
     *
     * @param  NULL|string $path Docker path
     * @return bool
     */
    public static function isDockerDirectory($path = NULL) {
        if ($path === NULL) {
            $path = getcwd();
        }

        $dockerFileList = array(
           'docker-compose.yml',
           'fig.yml',
        );

        foreach($dockerFileList as $dockerFile) {
            $filePath = $path . '/' . $dockerFile;

            if(file_exists($filePath)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Get docker instance prefix
     *
     * @param  NULL|string $path Docker path
     * @return mixed|string
     */
    public static function getDockerInstancePrefix($path = NULL) {
        if ($path === NULL) {
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
     * @return string
     */
    public static function getDockerInstanceName($containerName, $containerNumber = 1, $path = NULL) {
        $dockerName = array(
            \CliTools\Utility\DockerUtility::getDockerInstancePrefix($path),
            (string)$containerName,
            (int)$containerNumber,
        );

        return implode('_', $dockerName);
    }

}
