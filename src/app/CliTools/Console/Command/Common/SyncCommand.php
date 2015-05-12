<?php

namespace CliTools\Console\Command\Common;

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

use CliTools\Utility\PhpUtility;
use CliTools\Utility\UnixUtility;
use CliTools\Console\Builder\CommandBuilder;
use CliTools\Console\Builder\SelfCommandBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Project working path
     *
     * @var string|boolean|null
     */
    protected $workingPath;

    /**
     * Temporary storage dir
     *
     * @var
     */
    protected $tempDir;

    /**
     * Sync configuration
     *
     * @var array
     */
    protected $config = array();

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('sync')
            ->setDescription('Sync project')
//            ->addOption(
//                'sync',
//                null,
//                InputOption::VALUE_NONE,
//                'Sync project from live server'
//            )
            ->addOption(
                'backup',
                null,
                InputOption::VALUE_NONE,
                'Backup project to shared server'
            )
            ->addOption(
                'restore',
                null,
                InputOption::VALUE_NONE,
                'Restore project from shared server'
            );
    }

    /**
     * Execute command
     *
     * @param  InputInterface  $input  Input instance
     * @param  OutputInterface $output Output instance
     *
     * @return int|null|void
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        $this->workingPath = UnixUtility::findFileInDirectortyTree('clisync.ini');

        if (empty($this->workingPath)) {
            $this->output->writeln('<error>No clisync.ini found in tree</error>');
            return 1;
        }

        $this->output->writeln('<comment>Found clisync.ini directory: ' . $this->workingPath . '</comment>');

        $this->readConfiguration();
        $this->startup();

        try {
            if ($input->getOption('backup')) {
                $this->backupTask();
            } elseif ($input->getOption('restore')) {
                $this->restoreTask();
            }
        } catch (\Exception $e) {
            $this->cleanup();
            throw $e;
        }

        $this->cleanup();
    }

    /**
     * Read and validate configuration
     */
    protected function readConfiguration() {
        $this->config = parse_ini_file($this->workingPath . '/clisync.ini', true);
    }

    /**
     * Startup task
     */
    protected function startup() {
        $this->tempDir = '/tmp/.clisync-'.getmypid();
        $this->clearTempDir();
        PhpUtility::mkdir($this->tempDir, 0777, true);
        PhpUtility::mkdir($this->tempDir . '/mysql/', 0777, true);
    }

    /**
     * Cleanup task
     */
    protected function cleanup() {
        $this->clearTempDir();
    }

    /**
     * Clear temp. storage directory if exists
     */
    protected function clearTempDir() {
        // Remove storage dir
        if (!empty($this->tempDir) && is_dir($this->tempDir)) {
            $command = new CommandBuilder('rm', '-rf');
            $command->addArgumentSeparator()
                    ->addArgument($this->tempDir);
            $command->executeInteractive();
        }
    }

    /**
     * Backup task
     */
    protected function backupTask() {
        // ##################
        // Backup dirs
        // ##################
        $source = $this->workingPath;
        $target = $this->config["shared"]["server"] . '/data/';
        $command = $this->createShareRsyncCommand($source, $target, true);
        $command->executeInteractive();

        // ##################
        // Backup dirs
        // ##################
        foreach ($this->config["shared"]["database"] as $database) {
            $this->output->writeln('<info>Dumping database ' . $database . '</info>');

            // dump database
            $dumpFile = $this->tempDir . '/mysql/' . $database . '.sql.bz2';

            $mysqldump = new SelfCommandBuilder();
            $mysqldump->addArgumentTemplate('mysql:backup %s %s', $database, $dumpFile);

            if (!empty($this->config['shared']['database-filter'])) {
                $mysqldump->addArgumentTemplate('--filter=%s', $this->config['shared']['database-filter']);
            }

            $mysqldump->executeInteractive();
        }

        // ##################
        // Backup mysql dump
        // ##################
        $source = $this->tempDir;
        $target = $this->config["shared"]["server"] . '/dump/';
        $command = $this->createShareRsyncCommand($source, $target, false);
        $command->executeInteractive();
    }

    /**
     * Restore task
     */
    protected function restoreTask() {
        // ##################
        // Restore dirs
        // ##################
        $source = $this->config["shared"]["server"] . '/data/';
        $target = $this->workingPath;
        $command = $this->createShareRsyncCommand($source, $target, true);
        $command->executeInteractive();

        // ##################
        // Restore mysql dump
        // ##################
        $source = $this->config["shared"]["server"] . '/dump/';
        $target = $this->tempDir;
        $command = $this->createShareRsyncCommand($source, $target, false);
        $command->executeInteractive();

        $iterator = new \DirectoryIterator($this->tempDir . '/mysql');
        foreach ($iterator as $item) {
            // skip dot
            if ($item->isDot()) {
                continue;
            }

            list($database) = explode('.', $item->getFilename(), 2);

            if (!empty($database)) {
                $this->output->writeln('<info>Restoring database ' . $database . '</info>');

                $mysqldump = new SelfCommandBuilder();
                $mysqldump->addArgumentTemplate('mysql:restore %s %s', $database, $item->getPathname());
                $mysqldump->executeInteractive();
            }
        }
    }

    /**
     * Create rsync command for share sync
     *
     * @return CommandBuilder
     */
    protected function createShareRsyncCommand($source, $target, $useExcludeInclude = false) {
        $this->output->writeln('<info>Sync from ' . $source . ' to ' . $target . '</info>');

        $command = new CommandBuilder('rsync', '-rlptD --delete-after');

        // Set include and exclude
        if ($useExcludeInclude) {
            foreach ($this->config["shared"]["directory"] as $directory) {
                $command->addArgumentTemplate('--include=%s', $directory);
            }
            $command->addArgumentTemplate('--exclude=%s', '*');
        }

        $source = rtrim($source, '/') . '/';
        $target = rtrim($target, '/') . '/';

        // Set source and target
        $command->addArgument($source)
                ->addArgument($target);

        return $command;
    }

}
