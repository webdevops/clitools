<?php

namespace CliTools\Console\Command\Php;

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

class TraceCommand extends \CliTools\Console\Command\AbstractTraceCommand {

    /**
     * Process names for strace'ing
     *
     * @var array
     */
    protected $traceProcessNameList = array('php5-fpm', 'php-fpm', 'php5', 'php');

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('php:trace')
            ->setDescription('Debug PHP processes with strace');
        parent::configure();
    }

}
