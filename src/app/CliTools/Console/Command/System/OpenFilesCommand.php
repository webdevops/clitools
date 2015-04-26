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

use CliTools\Utility\FormatUtility;
use CliTools\Console\Builder\CommandBuilder;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OpenFilesCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('system:openfiles')
            ->setDescription('List swap usage');
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
        $this->elevateProcess($input, $output);

        $procList       = array();
        $openFilesTotal = 0;

        $command = new CommandBuilder('lsof', '-n');
        $command->addPipeCommand( new CommandBuilder('grep', '-oE \'^[a-z]+\'') )
            ->addPipeCommand( new CommandBuilder('sort') )
            ->addPipeCommand( new CommandBuilder('uniq', '-c') )
            ->addPipeCommand( new CommandBuilder('sort', '-n'))
            ->setOutputRedirect(CommandBuilder::OUTPUT_REDIRECT_NO_STDERR);
        $execOutput = $command->execute()->getOutput();

        foreach ($execOutput as $execOutputLine) {
            // get open files and proc name from output
            list($procOpenFiles, $procName) = explode(' ', trim($execOutputLine), 2);

            // add to total stats
            $openFilesTotal += $procOpenFiles;

            $procList[] = array(
                'name'       => $procName,
                'open_files' => $procOpenFiles,
            );
        }

        // ########################
        // Output
        // ########################
        /** @var \Symfony\Component\Console\Helper\Table $table */
        $table = new Table($output);
        $table->setHeaders(array('Process', 'Open Files'));

        foreach ($procList as $procRow) {
            $procRow['open_files'] = FormatUtility::number($procRow['open_files']);
            $table->addRow(array_values($procRow));
        }

        // Stats: average
        $table->addRow(new TableSeparator());
        $statsRow               = array();
        $statsRow['name']       = 'Total';
        $statsRow['open_files'] = FormatUtility::number($openFilesTotal);
        $table->addRow(array_values($statsRow));

        $table->render();

        return 0;
    }
}
