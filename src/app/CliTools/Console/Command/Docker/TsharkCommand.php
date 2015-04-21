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
use Symfony\Component\Console\Input\InputOption;
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
            )
            ->addOption('full',
                null,
                InputOption::VALUE_NONE,
                'Show full output (if supported by protocol)'
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

        $protocol   = $input->getArgument('protocol');
        $fullOutput = $input->getOption('full');

        switch ($protocol) {
            case 'http':
                if ($fullOutput) {
                    $args = 'tcp port 80 or tcp port 443 -V -R "http.request || http.response"';
                } else {
                    $args = '-z proto,colinfo,http.request.uri,http.request.uri -R http.request.uri';
                }
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

        return 0;
    }
}
