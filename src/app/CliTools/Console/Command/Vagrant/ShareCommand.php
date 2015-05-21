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

use CliTools\Console\Shell\CommandBuilder\CommandBuilder;
use CliTools\Console\Shell\CommandBuilder\SelfCommandBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShareCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('vagrant:share')
            ->setDescription('Start share for vagrant');
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
                                ->addArgument('--remove=*.vagrantshare.com')
                                ->addArgumentTemplate('--duplicate=%s', $domainName . '.vagrantshare.com')
                                ->execute();

                            $domainFound = true;
                        }
                    }
                }
            }

        };

        $opts = array(
            'runningCallback' => $runningCallback,
        );

        $vagrant = new CommandBuilder('vagrant', 'share');
        $vagrant
            ->addArgumentRaw('--http 80')
            ->addArgumentRaw('--https 443')
            ->executeInteractive($opts);
    }
}
