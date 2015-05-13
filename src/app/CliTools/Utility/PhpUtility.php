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

class PhpUtility {

    /**
     * Get content of file
     *
     * @param  string $file Filename
     * @return string
     */
    public static function fileGetContents($file) {
        if (!is_file($file) || !is_readable($file)) {
            throw new \RuntimeException('Could not read "' . $file . '"');
        }

        return file_get_contents($file);
    }

    /**
     * Get content of file
     *
     * @param string $file    Filename
     * @param string $content Content
     */
    public static function filePutContents($file, $content) {
        if (!file_put_contents($file, $content)) {
            throw new \RuntimeException('Could not write "' . $file . '"');
        }
    }

    /**
     * Change current working directory
     *
     * @param string $path Target path
     * @throws \RuntimeException
     */
    public static function chdir($path) {
        if (!is_dir($path) || !chdir($path)) {
            throw new \RuntimeException('Could not change working directory to "' . $path . '"');
        }
    }

    /**
     * Create new directory
     *
     * @param string    $path      Directory
     * @param integer   $mode      Perms
     * @param boolean   $recursive Creation of nested directories
     * @param resource  $context   Context
     * @throws \RuntimeException
     */
    public static function mkdir($path, $mode = 0777, $recursive = false, $context = null) {
        if ($context !== null) {
            $res = mkdir($path, $mode, $recursive, $context);
        } else {
            $res = mkdir($path, $mode, $recursive);
        }

        if (!$res) {
            throw new \RuntimeException('Could not create directory "' . $path . '"');
        }
    }

    /**
     * Remove file
     *
     * @param string $path Path to file
     * @throws \RuntimeException
     */
    public static function unlink($path) {
        if (!unlink($path)) {
            throw new \RuntimeException('Could not change working directory to "' . $path . '"');
        }
    }

    /**
     * Fetch content from url using curl
     *
     * @param string   $url      Url
     * @param callable $progress Progress callback
     *
     * @return mixed
     */
    public static function curlFetch($url, callable $progress = null) {
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_VERBOSE, 0);
        curl_setopt($curlHandle, CURLOPT_HEADER, 0);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curlHandle, CURLOPT_USERAGENT, 'CliTools ' . CLITOOLS_COMMAND_VERSION . '(https://github.com/mblaschke/vagrant-clitools)');

        if($progress) {
            curl_setopt($curlHandle, CURLOPT_NOPROGRESS, false);
            curl_setopt($curlHandle, CURLOPT_PROGRESSFUNCTION, $progress);
        }

        $ret = curl_exec($curlHandle);
        if (curl_errno($curlHandle) || empty($ret)) {
            throw new \RuntimeException('Could not fetch url "' . $url . '", error: ' . curl_error($curlHandle));
        }
        curl_close($curlHandle);

        return $ret;
    }
}
