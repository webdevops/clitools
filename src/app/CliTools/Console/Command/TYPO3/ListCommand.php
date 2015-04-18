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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('typo3:list')
            ->setDescription('List all TYPO3 instances')
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
        $basePath        = $this->getApplication()->getConfigValue('config', 'www_base_path', '/var/www/');
        $maxDepth        = 3;

        $basePath = Typo3Utility::guessBestTypo3BasePath($basePath, $input, 'path');

        $versionFileList = array(
            // 6.x version
            '/typo3/sysext/core/Classes/Core/SystemEnvironmentBuilder.php' => '/define\(\'TYPO3_version\',[\s]*\'([^\']+)\'\)/i',
            // 4.x version
            '/t3lib/config_default.php'                                    => '/\$TYPO_VERSION[\s]*=[\s]*\'([^\']+)\'/i',
        );

        // ####################
        // Find and loop through TYPO3 instances
        // ####################
        $typo3List = array();

        foreach (Typo3Utility::getTypo3InstancePathList($basePath, $maxDepth) as $dirPath) {
            $typo3Version = null;
            $typo3Path    = $dirPath;

            // Detect version (dirty way...)
            foreach ($versionFileList as $versionFile => $versionRegExp) {
                $versionFile = $dirPath . $versionFile;

                if (file_exists($versionFile)) {
                    $tmp = file_get_contents($versionFile);
                    if (preg_match($versionRegExp, $tmp, $matches)) {
                        $typo3Version = $matches[1];
                        break;
                    }
                }
            }

            if (strpos($typo3Version, '6') === 0) {
                // TYPO3 6.x
                $typo3Version = '<info>' . $typo3Version . '</info>';
            } elseif (!empty($typo3Version)) {
                // TYPO3 4.x
                $typo3Version = '<comment>' . $typo3Version . '</comment>';
            } else {
                // Unknown
                $typo3Version = '<error>unknown</error>';
            }

            $typo3List[] = array(
                $typo3Path,
                $typo3Version,
            );
        }

        $table = new Table($output);
        $table->setHeaders(array('Path', 'Version'));
        foreach ($typo3List as $row) {
            $table->addRow(array_values($row));
        }

        $table->render();

        return 0;
    }
}
