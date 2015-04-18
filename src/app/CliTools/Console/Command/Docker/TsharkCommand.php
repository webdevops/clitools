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

use CliTools\Utility\CommandExecutionUtility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TsharkCommand extends AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('docker:tshark')
            ->setDescription('Start tshark with docker')
            ->addArgument(
                'protocol',
                InputArgument::REQUIRED,
                'Protocol'
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
        $this->elevateProcess($input, $output);

        $container = 'main';

        $protocol = $input->getArgument('protocol');

        switch ($protocol) {
            case 'http':
                $args = 'tcp -i docker0 port 80 or tcp port 443 -V -R "http.request || http.response"';
                break;

            case 'smtp':
            case 'mail':
                $args = ' -f "port 25" -R "smtp"';
                break;

            default:
                $output->writeln('<error>Protocol not supported (supported: http, smtp)</error>');
                return 1;
                break;
        }

        CommandExecutionUtility::execInteractive('tshark', '-i docker0 ' . $args);

        $this->executeDockerExec($container, 'bash');

        return 0;
    }
}
