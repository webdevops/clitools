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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CliTools\Console\Builder\RemoteCommandBuilder;

class ShellCommand extends AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('docker:shell')
            ->setDescription('Enter shell in docker container')
            ->addArgument(
                'container',
                InputArgument::OPTIONAL,
                'Container'
            )
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED,
                'User for sudo'
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
        $container = $this->getApplication()->getConfigValue('docker', 'container');

        if ($input->getArgument('container')) {
            $container = $input->getArgument('container');
        }

        if ($input->getOption('user')) {
            // User user by option
            $cliUser = $input->getOption('user');
        } else {
            // Use docker env
            $cliUser = $this->getDockerEnv($container, 'CLI_USER');
        }

        $command = new RemoteCommandBuilder('bash');

        if (!empty($cliUser)) {
            // sudo wrapping as cli user
            $commandSudo = new RemoteCommandBuilder('sudo', '-H -E -u %s', array($cliUser));
            $commandSudo->append($command, false);
            $command = $commandSudo;
        }

        $ret = $this->executeDockerExec($container, $command);

        return $ret;
    }
}
