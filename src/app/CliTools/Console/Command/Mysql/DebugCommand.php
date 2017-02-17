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
use CliTools\Shell\CommandBuilder\DockerExecCommandBuilder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class DebugCommand extends AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('mysql:debug')
             ->setAliases(array('mysql:querylog'))
             ->setDescription(
                'Debug mysql connections'
             )
             ->addArgument(
                'grep',
                InputArgument::OPTIONAL,
                'Grep'
             )
            ->addOption(
                'keep-log',
                null,
                InputOption::VALUE_NONE,
                'Do not delete log after closing'
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
        $debugLogLocation = $this->getApplication()
                                 ->getConfigValue('db', 'debug_log_dir', '/tmp');
        $debugLogDir      = dirname($debugLogLocation);

        $keepLog  = (bool)$input->getOption('keep-log');

        $output->writeln('<h2>Starting MySQL general query log</h2>');

        // Create directory if not exists
        if (!is_dir($debugLogDir)) {
            if (!mkdir($debugLogDir, 0777, true)) {
                $output->writeln('<p-error>Could not create "' . $debugLogDir . '" directory</p-error>');
                throw new \CliTools\Exception\StopException(1);
            }
        }

        $debugLogLocation .= 'mysql_' . getmypid() . '.log';
        $output->writeln('<p>Set general_log_file to ' . $debugLogLocation . '</p>');
        $query = 'SET GLOBAL general_log_file = ' . $this->mysqlQuote($debugLogLocation);
        $this->execSqlCommand($query);

        // Enable general log
        $output->writeln('<p>Enabling general log</p>');
        $query = 'SET GLOBAL general_log = \'ON\'';
        $this->execSqlCommand($query);

        // Setup teardown cleanup
        $tearDownFunc = function () use ($output, $debugLogLocation, $keepLog) {
            // Disable general log
            $output->writeln('<p>Disabling general log</p>');
            $query = 'SET GLOBAL general_log = \'OFF\'';
            $this->execSqlCommand($query);

            if (!$keepLog) {
                $output->writeln('<p>Deleting logfile</p>');
                $command = $this->commandBuilderFactory('rm', ['-f', $debugLogLocation]);
                $command->executeInteractive();
            } else {
                $output->writeln('<p>Keeping logfile</p>');
            }
        };
        $this->getApplication()
             ->registerTearDown($tearDownFunc);

        // Read grep value
        $grep = null;
        if ($input->hasArgument('grep')) {
            $grep = $input->getArgument('grep');
        }

        if ($this->input->getOption('docker-compose') || $this->input->getOption('docker')) {
            $command = new DockerExecCommandBuilder('tail', ['-f', $debugLogLocation]);
            $command->setDockerContainer($this->dockerContainer);
            $command->executeInteractive();
        } else {
            $this->showLog([$debugLogLocation], $input, $output, $grep, ['-n 0']);
        }

        return 0;
    }
}
