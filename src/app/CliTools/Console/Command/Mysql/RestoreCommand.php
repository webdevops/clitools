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

use CliTools\Shell\CommandBuilder\CommandBuilder;
use CliTools\Utility\PhpUtility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestoreCommand extends AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

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
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $database = $input->getArgument('db');
        $dumpFile = $input->getArgument('file');

        if (!is_file($dumpFile) || !is_readable($dumpFile)) {
            $output->writeln('<p-error>File is not readable</p-error>');

            return 1;
        }

        $dumpFileType = PhpUtility::getMimeType($dumpFile);

        $output->writeln('<h2>Restoring dump "' . $dumpFile . '" into database "' . $database . '"</h2>');

        $output->writeln('<p>Creating database</p>');
        $this->execSqlCommand('DROP DATABASE IF EXISTS ' . addslashes($database));
        $this->execSqlCommand('CREATE DATABASE ' . addslashes($database));

        $commandMysql = $this->createMysqlCommand($database, '--one-database');

        $commandFile = new CommandBuilder();
        $commandFile->addArgument($dumpFile);
        $commandFile->addPipeCommand($commandMysql);

        switch ($dumpFileType) {
            case 'application/x-bzip2':
                $output->writeln('<p>Using BZIP2 decompression</p>');
                $commandFile->setCommand('bzcat');
                break;

            case 'application/gzip':
            case 'application/x-gzip':
                $output->writeln('<p>Using GZIP decompression</p>');
                $commandFile->setCommand('gzip')->addArgument('-dc');
                break;

            case 'application/x-lzma':
            case 'application/x-xz':
                $output->writeln('<p>Using LZMA decompression</p>');
                $commandFile->setCommand('xzcat');
                break;

            default:
                $output->writeln('<p>Using plaintext (no decompression)</p>');
                $commandFile->setCommand('cat');
                break;
        }

        $output->writeln('<p>Reading dump</p>');
        $commandFile->executeInteractive();

        $output->writeln('<h2>Database "' . $database . '" restored</h2>');

        return 0;
    }


}
