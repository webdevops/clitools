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

use CliTools\Console\Builder\CommandBuilder;
use CliTools\Console\Builder\SelfCommandBuilder;
use Symfony\Component\Console\Input\InputArgument;
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

        $this->createDockerInstance($path);
        $this->startDockerInstance($path);

        return 0;
    }

    /**
     * Create docker instance from git repository
     *
     * @param string $path Path
     */
    protected function createDockerInstance($path) {
        $boilerplateRepo = $this->getApplication()->getConfigValue('docker', 'boilerplate');

        $this->output->writeln('<comment>Create new docker boilerplate in "' . $path . '"</comment>');

        $command = new CommandBuilder('git','clone --branch=master --recursive %s %s', array($boilerplateRepo, $path));
        $command->executeInteractive();
    }

    /**
     * Build and startup docker instance
     *
     * @param string $path Path
     */
    protected function startDockerInstance($path) {
        $this->output->writeln('<comment>Building docker containers "' . $path . '"</comment>');

        \CliTools\Utility\PhpUtility::chdir($path);

        $command = new SelfCommandBuilder();
        $command->addArgument('docker:up');
        $command->executeInteractive();
    }
}
