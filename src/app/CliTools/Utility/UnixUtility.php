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

use CliTools\Utility\CommandExecutionUtility;

abstract class UnixUtility {

    /**
     * Get LSB System Description
     *
     * @return string
     */
    public static function lsbSystemDescription() {
        $lsbDesc = '';
        CommandExecutionUtility::execRaw('lsb_release -d | cut -f 2 -d ":"', $lsbDesc);
        $lsbDesc = trim(implode($lsbDesc));

        return $lsbDesc;
    }

    /**
     * Get CPU Count
     *
     * @return string
     */
    public static function cpuCount() {
        $cpuCount = '';
        CommandExecutionUtility::execRaw('nproc', $cpuCount);
        $cpuCount = trim(implode($cpuCount));

        return $cpuCount;
    }

    /**
     * Get Memory Count
     *
     * @return string
     */
    public static function memorySize() {
        $memSize = '';
        CommandExecutionUtility::execRaw('cat /proc/meminfo | awk \'match($1,"MemTotal") == 1 {print $2}\'', $memSize);
        $memSize = (int)trim(implode($memSize));

        // in bytes
        $memSize *= 1024;

        return $memSize;
    }

    /**
     * Get kernel version
     *
     * @return string
     */
    public static function kernelVersion() {
        $ret = '';
        CommandExecutionUtility::execRaw('uname -r', $ret);
        $ret = trim(implode($ret));

        return $ret;
    }

    /**
     * Get docker version
     *
     * @return string
     */
    public static function dockerVersion() {
        $ret = '';
        try {
            CommandExecutionUtility::execRaw('docker --version', $ret);
            $ret = trim(implode($ret));
        } catch (\Exception $e) {
            // no docker found?!
        }

        return $ret;
    }

    /**
     * Get mount info list
     *
     * @return string
     */
    public static function mountInfoList() {
        $discList = '';
        CommandExecutionUtility::execRaw('df -a --type=ext3 --type=ext4 --portability | tail --lines=+2 | awk \'{ print $6 " " $4 " " $5 }\'', $discList);

        $ret = array();
        foreach ($discList as $line) {
            list($disc, $capacity, $usage) = explode(' ', $line);
            $ret[$disc]['capacity'] = $capacity * 1024;
            $ret[$disc]['usage']    = $usage;
            $ret[$disc]['usageInt'] = (int)$usage;
        }

        return $ret;
    }

    /**
     * Get network interfaces as list
     *
     * @param  string $regExp Regular expression for matching
     * @return array
     */
    public static function networkInterfaceList($regExp) {
        $sysDir = '/sys/class/net/';

        $netInterfaceList = array();
        $dirIterator = new \DirectoryIterator($sysDir);
        foreach ($dirIterator as $dirEntry) {
            /** @var \DirectoryIterator $dirEntry */

            // skip dot
            if ($dirEntry->isDot()) {
                continue;
            }

            // skip virtual interfaces
            if(strpos($dirEntry->getFilename(), 'veth') === 0) {
                continue;
            }

            // Get interface name and remove all non printable chars
            $interfaceName = (string)$dirEntry->getFilename();
            $interfaceName = preg_replace('/[^[:print:]]/i', '', $interfaceName);

            // Filter interface if not matching
            if (!empty($regExp) && !preg_match($regExp, $interfaceName)) {
                continue;
            }

            if (!empty($interfaceName) && $dirEntry->isDir()) {
                $netInterfaceList[$interfaceName] = array();
            }
        }

        // ignore lo
        unset($netInterfaceList['lo']);

        foreach ($netInterfaceList as $netName => &$netConf) {
            $output = '';
            CommandExecutionUtility::exec('ifdata', $output, ' -pa %s', array($netName));
            $netConf['ipaddress'] = trim(implode('', $output));
        }
        unset($netConf);

        return $netInterfaceList;
    }

    /**
     * Get ip of default gateway
     *
     * @return null
     */
    public static function defaultGateway() {
        $output = null;
        CommandExecutionUtility::execRaw('ip route show | grep \'default\' | awk \'{print $3}\'', $output);
        $output = trim(implode('', $output));
        return $output;
    }

    /**
     * Send wall message
     *
     * @param  string $message Message
     */
    public static function sendWallMessage($message) {
        $output = '';
        CommandExecutionUtility::exec('echo', $output, '%s | wall 2> /dev/null', array($message));
    }

}