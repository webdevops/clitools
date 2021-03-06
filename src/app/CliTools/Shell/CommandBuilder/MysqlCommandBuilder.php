<?php

namespace CliTools\Shell\CommandBuilder;

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

use CliTools\Database\DatabaseConnection;

class MysqlCommandBuilder extends CommandBuilder
{

    /**
     * Initalized command
     */
    protected function initialize()
    {
        $this->addArgumentTemplate('--user=%s', DatabaseConnection::getDbUsername())
             ->addArgumentTemplate(
                 '--password=%s',
                 DatabaseConnection::getDbPassword()
             )
             ->addArgumentTemplate('--host=%s', DatabaseConnection::getDbHostname())
             ->addArgumentTemplate(
                 '--port=%s',
                 DatabaseConnection::getDbPort()
             );
    }

}
