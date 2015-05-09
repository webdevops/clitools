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

use CliTools\Console\Builder\CommandBuilder;

abstract class UnixUtility {

    /**
     * Path list
     *
     * @var array
     */
    protected static $pathList = null;

    /**
     * Get LSB System Description
     *
     * @return string
     */
    public static function lsbSystemDescription() {
        $command = new CommandBuilder('lsb_release', '-d');
        $command->addPipeCommand( new CommandBuilder('cut', '-f 2 -d ":"') );
        $ret = $command->execute()->getOutputString();

        $ret = trim($ret);

        return $ret;
    }

    /**
     * Get CPU Count
     *
     * @return string
     */
    public static function cpuCount() {
        $command = new CommandBuilder('nproc');
        $ret = $command->execute()->getOutputString();

        $ret = trim($ret);

        return $ret;
    }

    /**
     * Get Memory Count
     *
     * @return string
     */
    public static function memorySize() {
        $command = new CommandBuilder('cat', '/proc/meminfo');
        $command->addPipeCommand( new CommandBuilder('awk', '\'match($1,"MemTotal") == 1 {print $2}\'') );
        $ret = $command->execute()->getOutputString();

        // in bytes
        $ret = (int)trim($ret) * 1024;

        return $ret;
    }

    /**
     * Get kernel version
     *
     * @return string
     */
    public static function kernelVersion() {
        $command = new CommandBuilder('uname', '-r');
        $ret = $command->execute()->getOutputString();

        $ret = trim($ret);

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
            $command = new CommandBuilder('docker', '--version');
            $ret = $command->execute()->getOutputString();

            $ret = trim($ret);
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
        $command = new CommandBuilder('df', '-a --type=ext3 --type=ext4 --type vmhgfs --type vboxsf --portability');
        $command->addPipeCommand( new CommandBuilder('tail', '--lines=+2') )
                ->addPipeCommand( new CommandBuilder('awk', '\'{ print $6 " " $3 " " $4 " " $5 }\'') );
        $execOutput = $command->execute()->getOutput();

        $ret = array();
        foreach ($execOutput as $line) {
            list($disc, $capacity, $free, $usage) = explode(' ', $line);
            $ret[$disc]['capacity'] = $capacity * 1024;
            $ret[$disc]['free']     = $free * 1024;
            $ret[$disc]['usage']    = $usage;
            $ret[$disc]['usageInt'] = (int)$usage;
        }

        return $ret;
    }

    /**
     * Get network interfaces as list
     *
     * @param  string $regExp Regular expression for matching
     *
     * @return array
     */
    public static function networkInterfaceList($regExp) {
        $sysDir = '/sys/class/net/';

        $netInterfaceList = array();
        $dirIterator      = new \DirectoryIterator($sysDir);
        foreach ($dirIterator as $dirEntry) {
            /** @var \DirectoryIterator $dirEntry */

            // skip dot
            if ($dirEntry->isDot()) {
                continue;
            }

            // skip virtual interfaces
            if (strpos($dirEntry->getFilename(), 'veth') === 0) {
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
            $command = new CommandBuilder('ifdata', '-pa %s', array($netName));
            $netConf['ipaddress'] = trim($command->execute()->getOutputString());
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
        $command = new CommandBuilder('ip', 'route show');
        $command->addPipeCommand( new CommandBuilder('grep', '\'default\'') )
            ->addPipeCommand( new CommandBuilder('awk', '\'{print $3}\'') );
        $ret = $command->execute()->getOutputString();

        $ret = trim($ret);

        return $ret;
    }

    /**
     * Send wall message
     *
     * @param  string $message Message
     */
    public static function sendWallMessage($message) {
        $commandWall = new CommandBuilder('wall');
        $commandWall->setOutputRedirect(CommandBuilder::OUTPUT_REDIRECT_NULL);

        $command = new CommandBuilder('echo');
        $command->addArgument($message)
                ->addPipeCommand($commandWall);
        $command->execute();
    }

    /**
     * Get list of PATH entries
     *
     * @return array
     */
    public static function pathList() {

        if (self::$pathList === null) {
            $pathList = explode(':', getenv('PATH'));
            self::$pathList = array_map('trim', $pathList);
        }

        return self::$pathList;
    }

    /**
     * Check if command is exetuable
     *
     * @param string $command Command
     *
     * @return bool
     */
    public static function checkExecutable($command) {

        if (strpos($command,'/') !== false) {
            // command with path
            if (file_exists($command) && is_executable($command)) {
                return true;
            }
        } else {
            // command without path
            foreach (self::pathList() as $path) {
                $commandPath = $path . '/' . $command;

                if (file_exists($commandPath) && is_executable($commandPath)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Search directory upwards for a file
     *
     * @param string $file Filename
     * @param string $path Path
     * @return boolean|string
     */
    public static function findFileInDirectortyTree($file, $path = null) {
        $ret = false;

        // Set path to current path (if not specified)
        if ($path === null) {
            $path = getcwd();
        }

        if (!empty($path) && $path !== '/') {
            // Check if file exists in path
            if (file_exists($file)) {
                // Docker found
                $ret = $path;
            } else {
                // go up in directory
                $path .= '/../';
                $path = realpath($path);
                $ret  = self::searchUpDirForFile($path);
            }
        }

        return $ret;
    }
}
