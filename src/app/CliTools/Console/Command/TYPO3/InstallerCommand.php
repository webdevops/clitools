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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallerCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('typo3:installer')
            ->setDescription('Enable installer on all (or one specific) TYPO3 instances')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to TYPO3 instance'
            )->addOption(
                'remove',
                'r',
                InputOption::VALUE_NONE,
                'Remove installer file'
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
        $basePath        = $this->getApplication()->getConfigValue('config', 'www_base_path', '/var/www/');
        $maxDepth        = 3;
        $enableInstaller = true;

        $basePath = Typo3Utility::guessBestTypo3BasePath($basePath, $input, 'path');

        if ($input->getOption('remove')) {
            $enableInstaller = false;
        }

        // ####################
        // Find and loop through TYPO3 instances
        // ####################
        foreach (Typo3Utility::getTypo3InstancePathList($basePath, $maxDepth) as $dirPath) {

            $installFilePath = $dirPath . '/typo3conf/ENABLE_INSTALL_TOOL';

            if ($enableInstaller) {
                // ####################
                // Enable installer
                // ####################


                // Check if install tool file is already there
                if (is_file($installFilePath)) {
                    // Already existing, just update timestamp
                    touch($installFilePath);
                    $output->writeln('<comment>Already enabled on ' . $dirPath . '</comment>');
                } else {
                    // Not existing, create file
                    touch($installFilePath);
                    $output->writeln('<info>Enabled on ' . $dirPath . '</info>');
                }
            } else {
                // ####################
                // Disable installer
                // ####################

                // Check if install tool file is already there
                if (is_file($installFilePath)) {
                    // Remove installer file
                    unlink($installFilePath);
                    $output->writeln('<info>Disabled on ' . $dirPath . '</info>');
                } else {
                    $output->writeln('<comment>Not enabled on ' . $dirPath . '</comment>');
                }
            }
        }

        return 0;
    }
}
