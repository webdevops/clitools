<?php

namespace CliTools\Console\Command\System;

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

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnvCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('system:env')
            ->setDescription('List environment variables');
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

        $envNameList = array(
            'USER',
            'TYPO3_SYSTEM',
            'TYPO3_CONTEXT',
            'EDITOR',
            'http_proxy',
            'https_proxy',
        );

        $envList = array();

        foreach ($envNameList as $envName) {
            $envValue = getenv($envName);

            if (empty($envValue)) {
                $envValue = '<comment>(empty)</comment>';
            }

            $envList[] = array(
                $envName,
                $envValue,
            );
        }

        // ########################
        // Output
        // ########################
        /** @var \Symfony\Component\Console\Helper\Table $table */
        $table = new Table($output);
        $table->setHeaders(array('Environment variable', 'Value'));

        foreach ($envList as $envRow) {
            $table->addRow(array_values($envRow));
        }

        $table->render();

        return 0;
    }
}
