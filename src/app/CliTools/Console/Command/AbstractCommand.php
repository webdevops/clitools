<?php

namespace CliTools\Console\Command;

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

use CliTools\Utility\ConsoleUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CliTools\Console\Builder\FullSelfCommandBuilder;
use CliTools\Console\Builder\CommandBuilder;

abstract class AbstractCommand extends Command {

    /**
     * Input
     *
     * @var InputInterface
     */
    protected $input;

    /**
     * Input
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * Initializes the command just after the input has been validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output) {
        $this->input  = $input;
        $this->output = $output;

        ConsoleUtility::initialize($input, $output);
    }

    /**
     * Get full parameter list
     *
     * @param integer   $offset Parameter offset
     *
     * @return mixed
     */
    protected function getFullParameterList($offset = null) {
        $ret = $_SERVER['argv'];

        // remove requested offset
        if ($offset !== null) {
            $ret = array_splice($ret, $offset);
        }

        return $ret;
    }

    /**
     * Elevate process (exec sudo with same parameters)
     *
     * @param  InputInterface  $input  Input instance
     * @param  OutputInterface $output Output instance
     *
     * @return int|null|void
     */
    protected function elevateProcess(InputInterface $input, OutputInterface $output) {
        if (!$this->getApplication()->isRunningAsRoot()) {
            // Process is not running as root, trying to elevate to root
            $output->writeln('<comment>Elevating process using sudo...</comment>');

            try {
                $commandMyself = new FullSelfCommandBuilder();

                $commandSudo = new CommandBuilder('sudo');
                $commandSudo->append($commandMyself, false);
                $commandSudo->executeInteractive();
            } catch (\Exception $e) {
                // do not display exception here because it's a child process
            }
            exit(0);
        } else {
            // running as root
        }
    }

    /**
     * Show log, passthru multitail
     *
     * @param  array           $logList    List of log files
     * @param  InputInterface  $input      Input instance
     * @param  OutputInterface $output     Output instance
     * @param  string          $grep       Grep value
     * @param  array           $optionList Additional option list for multitail
     *
     * @return int|null|void
     * @throws \Exception
     */
    protected function showLog($logList, $input, $output, $grep = null, $optionList = null) {
        $this->elevateProcess($input, $output);

        // check if logfiles are accessable
        foreach ($logList as $log) {
            if (!is_readable($log)) {
                $output->writeln('<error>Can\'t read ' . $log . '</error>');

                return 1;
            }
        }

        $command = new CommandBuilder('multitail', '--follow-all');

        // Add grep
        if ($grep !== null) {
            $command->addArgumentTemplate('-E %s', $grep);
        }

        // Add log
        $command->addArgumentList($logList);
        $command->executeInteractive();

        return 0;
    }
}
