<?php

namespace CliTools\Console\Command\System;

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

use CliTools\Database\DatabaseConnection;
use CliTools\Console\Builder\CommandBuilder;
use CliTools\Utility\UnixUtility;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('system:version')
            ->setDescription('List common version');
    }

    /**
     * Execute command
     *
     * @param  InputInterface  $input  Input instance
     * @param  OutputInterface $output Output instance
     *
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output) {

        $versionList = array();

        // ############################
        // System (LSB Version)
        // ############################
        $versionRow = array(
            'system'  => 'System',
            'version' => UnixUtility::lsbSystemDescription(),
        );
        $versionList[] = array_values($versionRow);

        // ############################
        // PHP
        // ############################
        $versionList[] = array(
            'PHP',
            phpversion()
        );

        // ############################
        // MySQL
        // ############################
        $query      = 'SHOW VARIABLES LIKE \'version\'';
        $versionRow = DatabaseConnection::getList($query);

        $versionList[] = array(
            'MySQL',
            $versionRow['version']
        );

        // ############################
        // Apache
        // ############################
        $versionRow = array(
            'system'  => 'Apache',
            'version' => 'Unknown',
        );

        $command = new CommandBuilder('apache2ctl', '-v');
        $command->setOutputRedirect(CommandBuilder::OUTPUT_REDIRECT_NO_STDERR);
        $execOutput = $command->execute()->getOutput();

        foreach ($execOutput as $execOutputLine) {
            if (strpos($execOutputLine, ':') !== false) {
                list($tmpKey, $tmpVersion) = explode(':', trim($execOutputLine), 2);

                switch (strtolower($tmpKey)) {
                    case 'server version':
                        $versionRow['version'] = trim($tmpVersion);
                        break;
                }
            }
        }

        $versionList[] = array_values($versionRow);

        // ############################
        // Docker
        // ############################
        $versionRow = array(
            'system'  => 'Docker',
            'version' => \CliTools\Utility\UnixUtility::dockerVersion(),
        );
        $versionList[] = array_values($versionRow);

        // ############################
        // CliTools
        // ############################

        $versionList[] = array(
            'CliTools command',
            CLITOOLS_COMMAND_VERSION
        );

        // ########################
        // Output
        // ########################
        /** @var \Symfony\Component\Console\Helper\Table $table */
        $table = new Table($output);
        $table->setHeaders(array('System', 'Version'));

        foreach ($versionList as $versionRow) {
            $table->addRow(array_values($versionRow));
        }

        $table->render();

        return 0;
    }
}
