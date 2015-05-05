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
     * Change current working directory
     *
     * @param string $path Target path
     * @throws \RuntimeException
     */
    public static function chdir($path) {
        if (!chdir($path)) {
            throw new \RuntimeException('Could not change working directory to "' . $path . '"');
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
}
