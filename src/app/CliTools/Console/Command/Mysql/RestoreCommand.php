<?php

namespace CliTools\Console\Command\Mysql;

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
use CliTools\Utility\CommandExecutionUtility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestoreCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('mysql:restore')
            ->setDescription('Restore database')
            ->addArgument(
                'db',
                InputArgument::REQUIRED,
                'Database name'
            )
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'File (mysql dump)'
            );
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
        $database = $input->getArgument('db');
        $dumpFile = $input->getArgument('file');

        if (!is_file($dumpFile) || !is_readable($dumpFile)) {
            $output->writeln('<error>File is not readable</error>');

            return 1;
        }

        // Get mime type from file
        $finfo        = finfo_open(FILEINFO_MIME_TYPE);
        $dumpFileType = finfo_file($finfo, $dumpFile);
        finfo_close($finfo);

        // Dropping
        $output->writeln('<comment>Dropping Database "' . $database . '"...</comment>');
        $query = 'DROP DATABASE IF EXISTS ' . DatabaseConnection::sanitizeSqlDatabase($database);
        DatabaseConnection::exec($query);

        // Creating
        $output->writeln('<comment>Creating Database "' . $database . '"...</comment>');
        $query = 'CREATE DATABASE ' . DatabaseConnection::sanitizeSqlDatabase($database);
        DatabaseConnection::exec($query);

        // Inserting
        $output->writeln('<comment>Restoring dump into Database "' . $database . '"...</comment>');
        putenv('USER=' . DatabaseConnection::getDbUsername());
        putenv('MYSQL_PWD=' . DatabaseConnection::getDbPassword());

        switch ($dumpFileType) {
            case 'application/x-bzip2':
                CommandExecutionUtility::execInteractive('bzcat', '%s | mysql --user=%s %s',
                    array($dumpFile, DatabaseConnection::getDbUsername(), $database));
                break;

            case 'application/gzip':
                CommandExecutionUtility::execInteractive('gzcat', '%s | mysql --user=%s %s',
                    array($dumpFile, DatabaseConnection::getDbUsername(), $database));
                break;

            default:
                CommandExecutionUtility::execInteractive('cat', '%s | mysql --user=%s %s',
                    array($dumpFile, DatabaseConnection::getDbUsername(), $database));
                break;
        }

        $output->writeln('<info>Database "' . $database . '" restored</info>');

        return 0;
    }
}
