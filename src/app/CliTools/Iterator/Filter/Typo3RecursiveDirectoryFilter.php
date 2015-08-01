<?php

namespace CliTools\Iterator\Filter;

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

class Typo3RecursiveDirectoryFilter extends \CliTools\Iterator\Filter\RecursiveDirectoryFilter
{

    /**
     * List of directories which will be ignored (for sub search)
     *
     * @var array
     */
    protected $ignoreDirectoryList = array(
        'shared',
        'fileadmin',
        'typo3temp',
        't3lib',
        'typo3_src',
        'typo3src',
        'typo3conf',
        'typo3',
        'piwik',
        'cache',
        'tests',
        'classes',
        'uploads',
        'configuration',
        'resources',
    );

    /**
     * Filter for directories
     *
     * @return bool
     */
    public function accept()
    {
        if (!parent::accept()) {
            return false;
        }

        /** @var \DirectoryIterator $dirEntry */
        $dirEntry = $this->current();
        $dirName  = $dirEntry->getFilename();

        // Limit some known directory to optimize searching
        if (in_array(strtolower($dirName), $this->ignoreDirectoryList)) {
            return false;
        }

        return true;
    }
}
