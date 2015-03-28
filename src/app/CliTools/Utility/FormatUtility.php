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

class FormatUtility {

    /**
     * Format number in human readable format
     *
     * @param   integer|string $number Number
     * @param   integer $precision Output precision
     * @return  string
     */
    public static function number($number, $precision = 0) {
        $ret = number_format($number, $precision);
        return $ret;
    }

    /**
     * Format bytes in human readable format
     *
     * @param   integer|string $bytes Bytes
     * @param   integer $precision Output precision
     * @return  string
     */
    public static function bytes($bytes, $precision = 2) {
        $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        $ret = number_format(round($bytes, $precision), $precision) . ' ' . $units[$pow];
        return $ret;
    }
}