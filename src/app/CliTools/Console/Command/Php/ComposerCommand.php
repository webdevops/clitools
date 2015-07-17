<?php

namespace CliTools\Console\Command\Php;

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

class ComposerCommand extends \CliTools\Console\Command\AbstractCommand implements \CliTools\Console\Filter\AnyParameterFilterInterface {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('php:composer')
            ->setDescription('Search composer.json updir and start composer');
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

        if ($this->checkIfComposerJsonIsNeeded($paramList)) {
            $composerJsonPath = UnixUtility::findFileInDirectortyTree('composer.json');

            if (!empty($composerJsonPath)) {
                $path = dirname($composerJsonPath);
                $this->output->writeln('<comment>Found composer.json directory: ' . $path . '</comment>');
                // Switch to directory of docker-compose.yml
                PhpUtility::chdir($path);

                return $this->runComposer($paramList);
            } else {
                $this->output->writeln('<error>No composer.json found in tree</error>');
                return 1;
            }
        } else {
            return $this->runComposer($paramList);
        }
    }

    /**
     * Check if composer.json is needed for command
     *
     * @param array $paramList Parameter list for composer
     *
     * @return int|null|void
     */
    protected function checkIfComposerJsonIsNeeded(array $paramList) {
        if (empty($paramList)) {
            // no params -> show help
            return false;
        }

        $commandList = array(
            'archive',
            'dump-autoload',
            'dumpautoload',
            'info',
            'install',
            'remove',
            'require',
            'run-script',
            'status',
            'suggests',
            'update',
            'validate',
        );

        foreach ($commandList as $command) {
            if (in_array($command, $paramList, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Run composer command
     *
     * @param array $paramList Parameter list for composer
     *
     * @return int|null|void
     */
    protected function runComposer(array $paramList) {
        $composerCmd = $this->getApplication()->getConfigValue('bin', 'composer');

        $command = new CommandBuilder();
        $command->parse($composerCmd);

        if (!empty($paramList)) {
            $command->setArgumentList($paramList);
        }

        $command->executeInteractive();
    }
}
