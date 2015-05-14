<?php

namespace CliTools\Console\Command\Docker;

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

use CliTools\Console\Shell\CommandBuilder\CommandBuilder;
use CliTools\Console\Shell\CommandBuilder\SelfCommandBuilder;
use CliTools\Utility\PhpUtility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('docker:create')
            ->setDescription('Create new docker boilerplate')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Directory for new docker boilerplate instance'
            )
            ->addOption(
                'docker',
                'd',
                InputOption::VALUE_REQUIRED,
                'Docker Boilerplate repository'
            )
            ->addOption(
                'code',
                'c',
                InputOption::VALUE_REQUIRED,
                'Code repository'
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
        $path = $input->getArgument('path');

        if ($this->input->getOption('docker')) {
            // Custom boilerplate
            $boilerplateRepo = $this->input->getOption('docker');
        } else {
            // Boilerplate from config
            $boilerplateRepo = $this->getApplication()->getConfigValue('docker', 'boilerplate');
        }

        // Init docker boilerplate
        $this->createDockerInstance($path, $boilerplateRepo);

        // Init code
        if ($this->input->getOption('code')) {
            $this->initCode($path, $input->getOption('code'));
        }

        // Start docker
        $this->startDockerInstance($path);

        return 0;
    }

    /**
     * Create docker instance from git repository
     *
     * @param string $path Path
     * @param string $repo Repository
     */
    protected function createDockerInstance($path, $repo) {
        $this->output->writeln('<comment>Create new docker boilerplate in "' . $path . '"</comment>');

        $command = new CommandBuilder('git','clone --branch=master --recursive %s %s', array($repo, $path));
        $command->executeInteractive();
    }

    /**
     * Create docker instance from git repository
     *
     * @param string $path Path
     * @param string $repo Repository
     */
    protected function initCode($path, $repo) {
        $path .= '/code';

        $this->output->writeln('<comment>Initialize new code instance in "' . $path . '"</comment>');

        if (is_dir($path)) {
            if (file_exists($path . '/.gitkeep')) {
                // Remove gitkeep
                PhpUtility::unlink($path . '/.gitkeep');
            }

            // Remove code directory
            $command = new CommandBuilder('rmdir');
            $command->addArgumentSeparator()
                ->addArgument($path)
                ->executeInteractive();
        }

        $command = new CommandBuilder('git','clone --branch=master --recursive %s %s', array($repo, $path));
        $command->executeInteractive();
    }

    /**
     * Build and startup docker instance
     *
     * @param string $path Path
     */
    protected function startDockerInstance($path) {
        $this->output->writeln('<comment>Building docker containers "' . $path . '"</comment>');

        PhpUtility::chdir($path);

        $command = new SelfCommandBuilder();
        $command->addArgument('docker:up');
        $command->executeInteractive();
    }
}
