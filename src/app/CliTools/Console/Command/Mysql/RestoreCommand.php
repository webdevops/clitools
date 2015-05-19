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
use CliTools\Utility\PhpUtility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CliTools\Console\Shell\CommandBuilder\CommandBuilder;

class RestoreCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('mysql:restore')
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

        $dumpFileType = PhpUtility::getMimeType($dumpFile);

        if (DatabaseConnection::databaseExists($database)) {
            // Dropping
            $output->writeln('<info>Dropping Database "' . $database . '"...</info>');
            $query = 'DROP DATABASE IF EXISTS ' . DatabaseConnection::sanitizeSqlDatabase($database);
            DatabaseConnection::exec($query);
        }

        // Creating
        $output->writeln('<info>Creating Database "' . $database . '"...</info>');
        $query = 'CREATE DATABASE ' . DatabaseConnection::sanitizeSqlDatabase($database);
        DatabaseConnection::exec($query);

        // Inserting
        $output->writeln('<info>Restoring dump into Database "' . $database . '"...</info>');
        putenv('USER=' . DatabaseConnection::getDbUsername());
        putenv('MYSQL_PWD=' . DatabaseConnection::getDbPassword());


        $commandMysql = new CommandBuilder('mysql','--user=%s %s --one-database', array(DatabaseConnection::getDbUsername(), $database));

        $commandFile = new CommandBuilder();
        $commandFile->addArgument($dumpFile);
        $commandFile->addPipeCommand($commandMysql);

        switch ($dumpFileType) {
            case 'application/x-bzip2':
                $output->writeln('<comment>Using BZIP2 uncompression</comment>');
                $commandFile->setCommand('bzcat');
                break;

            case 'application/gzip':
            case 'application/x-gzip':
                $output->writeln('<comment>Using GZIP uncompression</comment>');
                $commandFile->setCommand('gzcat');
                break;

            case 'application/x-lzma':
            case 'application/x-xz':
            $output->writeln('<comment>Using LZMA uncompression</comment>');
                $commandFile->setCommand('xzcat');
                break;

            default:
                $output->writeln('<comment>Using plaintext (no uncompression)</comment>');
                $commandFile->setCommand('cat');
                break;
        }

        $commandFile->executeInteractive();

        $output->writeln('<info>Database "' . $database . '" restored</info>');

        return 0;
    }


}
