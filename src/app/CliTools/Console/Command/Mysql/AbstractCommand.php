<?php

namespace CliTools\Console\Command\Mysql;

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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends \CliTools\Console\Command\AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->addOption(
            'host',
            null,
            InputOption::VALUE_REQUIRED,
            'MySQL host'
        )
             ->addOption(
                 'port',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'MySQL port'
             )
             ->addOption(
                 'user',
                 'u',
                 InputOption::VALUE_REQUIRED,
                 'MySQL user'
             )
             ->addOption(
                 'password',
                 'p',
                 InputOption::VALUE_REQUIRED,
                 'MySQL host'
             );
    }

    /**
     * Initializes the command just after the input has been validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $dsn      = null;
        $user     = null;
        $password = null;
        $host     = DatabaseConnection::getDbHostname();
        $port     = DatabaseConnection::getDbPort();

        // host
        if ($this->input->hasOption('host') && $this->input->getOption('host')) {
            $host = $this->input->getOption('host');
            $dsn  = false;
        }

        // port
        if ($this->input->hasOption('port') && $this->input->getOption('port')) {
            $port = $this->input->getOption('port');
            $dsn  = false;
        }

        // rebuild dsn
        if ($dsn === false) {
            $dsn = 'mysql:host=' . urlencode($host) . ';port=' . (int)$port;
        }

        // user
        if ($this->input->hasOption('user') && $this->input->getOption('user')) {
            $user = $this->input->getOption('user');
        }

        // password
        if ($this->input->hasOption('password') && $this->input->getOption('password')) {
            $password = $this->input->getOption('password');
        }

        if ($dsn !== null || $user !== null || $password !== null) {
            DatabaseConnection::setDsn($dsn, $user, $password);
        }
    }
}
