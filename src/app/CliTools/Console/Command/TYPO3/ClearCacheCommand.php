<?php

namespace CliTools\Console\Command\TYPO3;

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

use CliTools\Utility\Typo3Utility;
use CliTools\Shell\CommandBuilder\CommandBuilder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCacheCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('typo3:clearcache')
            ->setDescription('Clear cache on all (or one specific) TYPO3 instances')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to TYPO3 instance'
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
        // ####################
        // Init
        // ####################
        $basePath = $this->getApplication()->getConfigValue('config', 'www_base_path', '/var/www/');
        $maxDepth = 3;

        $output->writeln('<h2>Clear TYPO3 cache</h2>');

        if ($input->getArgument('path')) {
            $basePath = $input->getArgument('path');
        }

        // ####################
        // Find and loop through TYPO3 instances
        // ####################
        foreach (Typo3Utility::getTypo3InstancePathList($basePath, $maxDepth) as $dirPath) {

            // Check if coreapi is installed
            if (!is_dir($dirPath . '/typo3conf/ext/coreapi/')) {
                $output->writeln('<p>EXT:coreapi is missing on ' . $dirPath . ', skipping</p>');
                continue;
            }

            $params = array(
                $dirPath . '/typo3/cli_dispatch.phpsh',
                'coreapi',
                'cache:clearallcaches'
            );

            $output->writeln('<p>Running clearcache command on ' . $dirPath . '</p>');
            try {

                $command = new CommandBuilder('php');
                $command->setArgumentList($params)
                    ->executeInteractive();
            } catch (\Exception $e) {
                $output->writeln('<p-error> Failed with exception: ' . $e->getMessage() . '</p-error>');
            }
        }

        return 0;
    }
}
