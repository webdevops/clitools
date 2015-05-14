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
use CliTools\Console\Shell\CommandBuilder\CommandBuilder;
use CliTools\Console\Shell\CommandBuilder\CommandBuilderInterface;
use CliTools\Utility\FilterUtility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BackupCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('mysql:backup')
             ->setDescription('Backup database')
             ->addArgument(
                 'db',
                 InputArgument::REQUIRED,
                 'Database name'
             )
             ->addArgument(
                 'file',
                 InputArgument::REQUIRED,
                 'File (mysql dump)'
             )->addOption(
                'filter',
                'f',
                InputOption::VALUE_REQUIRED,
                'Filter (eg. typo3)'
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
        $filter   = $input->getOption('filter');

        if (!DatabaseConnection::databaseExists($database)) {
            $output->writeln('<error>Database "' . $database . '" does not exists</error>');

            return 1;
        }

        $fileExt = pathinfo($dumpFile, PATHINFO_EXTENSION);

        // Inserting
        putenv('USER=' . DatabaseConnection::getDbUsername());
        putenv('MYSQL_PWD=' . DatabaseConnection::getDbPassword());

        $commandCompressor = null;

        switch ($fileExt) {
            case 'bz':
            case 'bz2':
            case 'bzip2':
                $output->writeln('<comment>Using BZIP2 compression</comment>');
                $commandCompressor = new CommandBuilder('bzip2');
                break;

            case 'gz':
            case 'gzip':
                $output->writeln('<comment>Using GZIP compression</comment>');
                $commandCompressor = new CommandBuilder('gzip');
                break;

            case 'lzma':
            case 'lz':
            case 'xz':
                $output->writeln('<comment>Using LZMA compression</comment>');
                $commandCompressor = new CommandBuilder('xz');
                $commandCompressor->addArgument('--compress')
                    ->addArgument('--stdout');
                break;
        }

        $command = new CommandBuilder('mysqldump','--user=%s %s --single-transaction', array(DatabaseConnection::getDbUsername(), $database));

        if (!empty($filter)) {
            $command = $this->addFilterArguments($command, $database, $filter);
        }

        if (!empty($commandCompressor)) {
            $command->addPipeCommand($commandCompressor);
            $commandCompressor->setOutputRedirectToFile($dumpFile);
        } else {
            $output->writeln('<comment>Using no compression</comment>');
            $command->setOutputRedirectToFile($dumpFile);
        }

        $command->executeInteractive();

        $output->writeln('<info>Database "' . $database . '" stored to "' . $dumpFile . '"</info>');
    }

    /**
     * Add filter to command
     *
     * @param CommandBuilderInterface $command  Command
     * @param string                  $database Database
     * @param string                  $filter   Filter name
     *
     * @return CommandBuilderInterface
     */
    protected function addFilterArguments(CommandBuilderInterface $commandDump, $database, $filter) {
        $command = $commandDump;

        // get filter
        $filterList = $this->getApplication()->getConfigValue('mysql-backup-filter', $filter);

        if (empty($filterList)) {
            throw new \RuntimeException('MySQL dump filters "' . $filter . '" not available"');
        }

        $this->output->writeln('<comment>Using filter "' . $filter . '"</comment>');

        // Get filtered tables
        $tableList = DatabaseConnection::tableList($database);
        $tableList = FilterUtility::mysqlTableFilter($tableList, $filterList);

        // Dump only structure
        $commandStructure = clone $command;
        $commandStructure->addArgument('--no-data');

        // Dump only data (only filtered tables)
        $commandData = clone $command;
        $commandData
            ->addArgument('--no-create-info')
            ->addArgumentList($tableList);

        // Combine both commands to one
        $command = new \CliTools\Console\Shell\CommandBuilder\OutputCombineCommandBuilder();
        $command
            ->addCommandForCombinedOutput($commandStructure)
            ->addCommandForCombinedOutput($commandData);

        return $command;
    }
}
