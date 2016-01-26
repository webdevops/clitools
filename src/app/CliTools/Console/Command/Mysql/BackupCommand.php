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
use CliTools\Shell\CommandBuilder\CommandBuilder;
use CliTools\Shell\CommandBuilder\CommandBuilderInterface;
use CliTools\Shell\CommandBuilder\MysqlCommandBuilder;
use CliTools\Utility\FilterUtility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BackupCommand extends AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

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
             )
             ->addOption(
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
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $database = $input->getArgument('db');
        $dumpFile = $input->getArgument('file');
        $filter   = $input->getOption('filter');

        if (!DatabaseConnection::databaseExists($database)) {
            $output->writeln('<p-error>Database "' . $database . '" does not exists</p-error>');

            return 1;
        }

        $output->writeln('<h2>Dumping database "' . $database . '" into file "' . $dumpFile . '"</h2>');

        $fileExt = pathinfo($dumpFile, PATHINFO_EXTENSION);

        // Inserting
        putenv('USER=' . DatabaseConnection::getDbUsername());
        putenv('MYSQL_PWD=' . DatabaseConnection::getDbPassword());

        $commandCompressor = null;

        switch ($fileExt) {
            case 'bz':
            case 'bz2':
            case 'bzip2':
                $output->writeln('<p>Using BZIP2 compression</p>');
                $commandCompressor = new CommandBuilder('bzip2');
                break;

            case 'gz':
            case 'gzip':
                $output->writeln('<p>Using GZIP compression</p>');
                $commandCompressor = new CommandBuilder('gzip');
                break;

            case 'lzma':
            case 'lz':
            case 'xz':
                $output->writeln('<p>Using LZMA compression</p>');
                $commandCompressor = new CommandBuilder('xz');
                $commandCompressor->addArgument('--compress')
                                  ->addArgument('--stdout');
                break;
        }

        $command = new MysqlCommandBuilder('mysqldump', '--single-transaction %s', array($database));

        if (!empty($filter)) {
            $command = $this->addFilterArguments($command, $database, $filter);
        }

        if (!empty($commandCompressor)) {
            $command->addPipeCommand($commandCompressor);
            $commandCompressor->setOutputRedirectToFile($dumpFile);
        } else {
            $output->writeln('<p>Using no compression</p>');
            $command->setOutputRedirectToFile($dumpFile);
        }

        $command->executeInteractive();

        $output->writeln('<h2>Database "' . $database . '" stored to "' . $dumpFile . '"</h2>');
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
    protected function addFilterArguments(CommandBuilderInterface $commandDump, $database, $filter)
    {
        $command = $commandDump;

        // get filter
        $filterList = $this->getApplication()
                           ->getConfigValue('mysql-backup-filter', $filter);

        if (empty($filterList)) {
            throw new \RuntimeException('MySQL dump filters "' . $filter . '" not available"');
        }

        $this->output->writeln('<comment>Using filter "' . $filter . '"</comment>');

        // Get filtered tables
        $tableList        = DatabaseConnection::tableList($database);
        $ignoredTableList = FilterUtility::mysqlIgnoredTableFilter($tableList, $filterList, $database);

        // Dump only structure
        $commandStructure = clone $command;
        $commandStructure->addArgument('--no-data');

        // Dump only data (only filtered tables)
        $commandData = clone $command;
        $commandData->addArgument('--no-create-info');

        if (!empty($ignoredTableList)) {
            $commandData->addArgumentTemplateMultiple('--ignore-table=%s', $ignoredTableList);
        }

        // Combine both commands to one
        $command = new \CliTools\Shell\CommandBuilder\OutputCombineCommandBuilder();
        $command->addCommandForCombinedOutput($commandStructure)
                ->addCommandForCombinedOutput($commandData);

        return $command;
    }
}
