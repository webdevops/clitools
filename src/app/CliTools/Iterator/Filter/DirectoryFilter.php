<?php

namespace CliTools\Iterator\Filter;

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

class DirectoryFilter extends \FilterIterator {

    /**
     * Filter for directories
     *
     * @return bool
     */
    public function accept() {
        /** @var \DirectoryIterator $dirEntry */
        $dirEntry = $this->current();

        if (!$dirEntry->isDir()) {
            return false;
        }

        if ($dirEntry->isDot()) {
            return false;
        }

        // Check if directory is readable and if it is possible to change into directory
        $dirPath = $dirEntry->getPathname();
        if (!is_readable($dirPath) && !is_executable($dirPath)) {
            return false;
        }

        return false;
    }

}