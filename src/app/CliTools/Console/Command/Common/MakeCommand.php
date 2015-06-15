<?php

namespace CliTools\Console\Command\Common;

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

use CliTools\Utility\UnixUtility;
use CliTools\Utility\PhpUtility;
use CliTools\Shell\CommandBuilder\CommandBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCommand extends \CliTools\Console\Command\AbstractCommand implements \CliTools\Console\Filter\AnyParameterFilterInterface {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('make')
            ->setDescription('Search Makefile updir and start makefile');
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
        $paramList = $this->getFullParameterList();
        $path = UnixUtility::findFileInDirectortyTree('Makefile');

        if (!empty($path)) {
            $path = dirname($path);
            $this->output->writeln('<comment>Found Makefile directory: ' . $path . '</comment>');

            // Switch to directory of docker-compose.yml
            PhpUtility::chdir($path);

            $command = new CommandBuilder('make');

            if (!empty($paramList)) {
                $command->setArgumentList($paramList);
            }

            $command->executeInteractive();
        } else {
            $this->output->writeln('<error>No Makefile found in tree</error>');

            return 1;
        }

        return 0;
    }
}
