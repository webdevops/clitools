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

use CliTools\Service\SelfUpdateService;
use CliTools\Utility\CommandExecutionUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('system:update')->setAliases(array('update'))->setDescription('Update system');
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
        if (!$this->getApplication()->isRunningAsRoot()) {
            $this->userUpdate($input, $output);
        }

        $this->elevateProcess($input, $output);

        $output->writeln('<info>Running system update...</info>');

        return $this->systemUpdate($input, $output);
    }

    /**
     * Run user update
     *
     * @param  InputInterface  $input  Input instance
     * @param  OutputInterface $output Output instance
     *
     * @return int|null|void
     */
    protected function userUpdate(InputInterface $input, OutputInterface $output) {
        // GIT pulls and other stuff

        // ##################
        // SSH Git repo update
        // ##################
        $reposDirectory = $this->getApplication()->getConfigValue('config', 'ssh_conf_path', '/opt/conf/ssh');

        if (is_dir($reposDirectory) && is_dir($reposDirectory . '/.git')) {
            // SSH Git repo exists, update now
            $originalCwd = getcwd();

            chdir($reposDirectory);

            try {
                // Update git repository
                $this->outputBlock($output, 'Running git update of ' . $reposDirectory);
                CommandExecutionUtility::execInteractive('git', 'pull');

                // Rebuild ssh config
                CommandExecutionUtility::execInteractive(CLITOOLS_COMMAND_CLI, 'user:rebuildsshconfig');
            } catch (\RuntimeException $e) {
                $msg = 'Running git update of ' . $reposDirectory . '... FAILED';
                $output->writeln('<error>' . $msg . '</error>');
                $errorMsgList[] = $msg;
            }

            chdir($originalCwd);
        }
    }

    /**
     * Run system update
     *
     * @param  InputInterface  $input  Input instance
     * @param  OutputInterface $output Output instance
     *
     * @return int|null|void
     */
    protected function systemUpdate(InputInterface $input, OutputInterface $output) {
        $errorMsgList = array();

        // ##################
        // System update
        // ##################
        try {
            $this->outputBlock($output, 'Running system package update');
            CommandExecutionUtility::execInteractive('apt-get', '%s --quiet', array('clean'));
            CommandExecutionUtility::execInteractive('apt-get', '%s --quiet', array('update'));
            CommandExecutionUtility::execInteractive('apt-get', '%s --fix-broken --assume-yes --quiet',
                array('dist-upgrade'));
            CommandExecutionUtility::execInteractive('apt-get', '%s --quiet', array('autoclean'));
        } catch (\RuntimeException $e) {
            $msg = 'Running system package update... FAILED';
            $output->writeln('<error>' . $msg . '</error>');
            $errorMsgList[] = $msg;
        }

        // ##################
        // npm update
        // ##################
        //try {
        //	$this->outputBlock($output, 'Running npm update');
        //	CommandExecutionUtility::execInteractive('npm', 'update -g');
        //} catch (\RuntimeException $e) {
        //	$msg = 'Running npm update... FAILED';
        //	$output->writeln('<error>' . $msg . '</error>');
        //	$errorMsgList[] = $msg;
        //}

        // ##################
        // clitools update
        // ##################
        try {
            $this->outputBlock($output, 'Running clitools update');
            $updateService = new SelfUpdateService($this->getApplication(), $output);
            $updateService->update();
        } catch (\RuntimeException $e) {
            $msg = 'Running clitools update... FAILED';
            $output->writeln('<error>' . $msg . '</error>');
            $errorMsgList[] = $msg;
        }

        // ##################
        // Composer update
        // ##################
        try {
            $this->outputBlock($output, 'Running composer update');
            CommandExecutionUtility::execInteractive('composer', '%s', array('self-update'));
        } catch (\RuntimeException $e) {
            $msg = 'Running composer update... FAILED';
            $output->writeln('<error>' . $msg . '</error>');
            $errorMsgList[] = $msg;
        }

        // ##################
        // Box.phar update
        // ##################
        try {
            $this->outputBlock($output, 'Running box.phar update');
            CommandExecutionUtility::execInteractive('box.phar', '%s', array('update'));
        } catch (\RuntimeException $e) {
            $msg = 'Running box.phar update... FAILED';
            $output->writeln('<error>' . $msg . '</error>');
            $errorMsgList[] = $msg;
        }

        // ##################
        // Misc
        // ##################

        // TODO

        // ##################
        // Summary
        // ##################
        if (!empty($errorMsgList)) {
            $output->writeln('');
            $output->writeln('');
            $output->writeln('<error>[WARNING] Some update tasks have failed!</error>');
            foreach ($errorMsgList as $errorMsg) {
                $output->writeln('  * ' . $errorMsg);
            }
        } else {
            $output->writeln('<info>Update successfully finished</info>');
        }

        return 0;
    }


    /**
     * Output block
     *
     * @param OutputInterface $output Output
     * @param string          $msg    Message
     */
    protected function outputBlock($output, $msg) {
        list($termWidth, $termHeight) = $this->getApplication()->getTerminalDimensions();
        $separator = '<info>' . str_repeat('-', $termWidth) . '</info>';

        $msg = str_repeat(' ', $termWidth - strlen($msg) - 10) . $msg;

        $output->writeln($separator);
        $output->writeln('<info>' . $msg . '</info>');
    }
}
