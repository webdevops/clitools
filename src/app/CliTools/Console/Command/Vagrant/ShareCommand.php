<?php

namespace CliTools\Console\Command\Vagrant;

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

use CliTools\Shell\CommandBuilder\CommandBuilder;
use CliTools\Shell\CommandBuilder\SelfCommandBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ShareCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('vagrant:share')
            ->setDescription('Start share for vagrant')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Specific name for the share'
            )
            ->addOption(
                'http',
                null,
                InputOption::VALUE_REQUIRED,
                'Local HTTP port to forward to'
            )
            ->addOption(
                'https',
                null,
                InputOption::VALUE_REQUIRED,
                'Local HTTPS port to forward to'
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                'Specific name for the share'
            )
            ->addOption(
                'ssh',
                null,
                InputOption::VALUE_NONE,
                'Allow \'vagrant connect --ssh\' access'
            )
            ->addOption(
                'ssh-no-password',
                null,
                InputOption::VALUE_NONE,
                'Key won\'t be encrypted with --ssh'
            )
            ->addOption(
                'ssh-port',
                null,
                InputOption::VALUE_REQUIRED,
                'Specific port for SSH when using --ssh'
            )
            ->addOption(
                '--ssh-once',
                null,
                InputOption::VALUE_NONE,
                'Allow \'vagrant connect --ssh\' only one time'
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

        $runningCallback = function($process, $status) {
            static $domainFound = false;
            if ($domainFound) {
                return;
            }

            $pid = $status['pid'];

            exec('pgrep -P ' . (int)$pid . ' | xargs ps -o command=', $output);

            if (!empty($output)) {
                foreach ($output as $line) {
                    if (preg_match('/register\.vagrantshare\.com/', $line)) {

                        if (preg_match('/-name ([^\s]+)/', $line, $matches)) {
                            $domainName = $matches[1];

                            $typo3Domain = new SelfCommandBuilder();
                            $typo3Domain
                                ->addArgument('typo3:domain')
                                ->addArgumentTemplate('--remove=%s', '*.vagrantshare.com')
                                ->addArgumentTemplate('--duplicate=%s', $domainName . '.vagrantshare.com')
                                ->execute();

                            $domainFound = true;
                        }
                    }
                }
            }
        };

        $cleanupCallback = function() {
            $typo3Domain = new SelfCommandBuilder();
            $typo3Domain
                ->addArgument('typo3:domain')
                ->addArgumentTemplate('--remove=%s', '*.vagrantshare.com')
                ->execute();
        };
        $this->getApplication()->registerTearDown($cleanupCallback);

        $opts = array(
            'runningCallback' => $runningCallback,
        );

        $vagrant = new CommandBuilder('vagrant', 'share');

        // Share name
        if ($input->getOption('name')) {
            $vagrant->addArgumentTemplate('--name %s', $input->getOption('name'));
        } elseif ($input->getArgument('name')) {
            $vagrant->addArgumentTemplate('--name %s', $input->getArgument('name'));
        }


        // HTTP port
        if ($input->getOption('http')) {
            $vagrant->addArgumentTemplate('--http %s', $input->getOption('http'));
        } else {
            $vagrant->addArgumentTemplate('--http %s', 80);
        }

        // HTTPS port
        if ($input->getOption('https')) {
            $vagrant->addArgumentTemplate('--http %s', $input->getOption('https'));
        } else {
            $vagrant->addArgumentTemplate('--https %s', 443);
        }


        // SSH stuff
        if ($input->getOption('ssh')) {
            $vagrant->addArgument('--ssh');
        }

        if ($input->getOption('ssh-no-password')) {
            $vagrant->addArgument('--ssh-no-password');
        }

        if ($input->getOption('ssh-port')) {
            $vagrant->addArgumentTemplate('--ssh-port %s', $input->getOption('ssh-port'));
        }

        if ($input->getOption('ssh-once')) {
            $vagrant->addArgument('--ssh-once');
        }


        $vagrant->executeInteractive($opts);
    }
}
