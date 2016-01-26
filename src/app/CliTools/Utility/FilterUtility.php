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

class FilterUtility
{

    /**
     * Filter mysql table list by filter
     *
     * @param array $tables  List of tables
     * @param array $filters List of filters
     *
     * @return array
     */
    public static function mysqlTableFilter(array $tables, array $filters)
    {
        $ret = array();

        foreach ($tables as $table) {
            foreach ($filters as $filter) {
                if (preg_match($filter, $table)) {
                    continue 2;
                }
            }
            $ret[] = $table;
        }

        return $ret;
    }

    /**
     * Filter mysql table list by filter
     *
     * @param array       $tables   List of tables
     * @param array       $filters  List of filters
     * @param string|null $database Database
     *
     * @return array
     */
    public static function mysqlIgnoredTableFilter(array $tables, array $filters, $database = null)
    {
        $ret = array();

        foreach ($tables as $table) {
            foreach ($filters as $filter) {
                if (preg_match($filter, $table)) {

                    if ($database !== null) {
                        $ret[] = $database . '.' . $table;
                    } else {
                        $ret[] = $table;
                    }
                    continue 2;
                }
            }
        }

        return $ret;
    }
}
