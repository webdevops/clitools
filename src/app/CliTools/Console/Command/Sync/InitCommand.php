<?php

namespace CliTools\Console\Command\Sync;

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

use CliTools\Utility\PhpUtility;
use CliTools\Console\Shell\CommandBuilder\EditorCommandBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('sync:init')
            ->setDescription('Create example clisync.yml');
    }

    /**
     * Execute command
     *
     * @param  InputInterface  $input  Input instance
     * @param  OutputInterface $output Output instance
     *
     * @return int|null|void
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        $cliSyncFilePath = getcwd() . '/' . AbstractCommand::CONFIG_FILE;

        if (file_exists($cliSyncFilePath)) {
            $this->output->writeln('<p-error>Configuration file ' . AbstractCommand::CONFIG_FILE . ' already exists</p-error>');
            return 1;
        }

        // fetch example
        $content = PhpUtility::fileGetContents(CLITOOLS_ROOT_FS . '/conf/clisync.yml');

        // store in current working dir
        PhpUtility::filePutContents($cliSyncFilePath, $content);

        // Start editor with file (if $EDITOR is set)
        try {
            $editor = new EditorCommandBuilder();
            $editor
                ->addArgument($cliSyncFilePath)
                ->executeInteractive();
        } catch (\Exception $e) {
            $this->output->writeln('<p-error>' . $e->getMessage() . '</p-error>');
        }

        $this->output->writeln('<info>Successfully created ' . AbstractCommand::CONFIG_FILE . ' </info>');
    }

}
