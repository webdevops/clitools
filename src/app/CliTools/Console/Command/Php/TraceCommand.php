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

use CliTools\Utility\CommandExecutionUtility;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class TraceCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('php:trace')
            ->setDescription('Debug PHP process with strace')
            ->addArgument(
                'grep',
                InputArgument::OPTIONAL,
                'Grep'
            )
            ->addOption(
                'c',
                null,
                InputOption::VALUE_NONE,
                'SysCall statistics'
            )
            ->addOption(
                'r',
                null,
                InputOption::VALUE_NONE,
                'Relative time'
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
        $pid        = null;
        $grep       = $input->getArgument('grep');
        $currentPid = posix_getpid();

        if (empty($pid)) {
            $phpProcessList = array(
                'all PHP processes' => 'all',
            );

            $cmdOutput = '';
            CommandExecutionUtility::execRaw('ps h -o pid,comm,args -C php5-fpm,php-fpm,php5,php', $cmdOutput);

            $pidList = array();
            foreach ($cmdOutput as $outputLine) {
                $outputLine      = trim($outputLine);
                $outputLineParts = preg_split('/[\s]+/', $outputLine);
                list($pid, $cmd) = $outputLineParts;

                $pid = (int)$pid;

                unset($outputLineParts[0], $outputLineParts[1]);
                $args = implode(' ', $outputLineParts);

                $cmd = $pid . ' [' . $cmd . '] ' . $args;

                // don't show current pid
                if ($pid === $currentPid) {
                    continue;
                }

                $pidList[]            = (int)$pid;
                $phpProcessList[$cmd] = (int)$pid;
            }

            $question = new ChoiceQuestion('Please choose PHP process for tracing', $phpProcessList);

            $questionDialog = new QuestionHelper();
            $pid            = $questionDialog->ask($input, $output, $question);
        }


        if (!empty($pid)) {
            $cmdArgs = array();
            switch ($pid) {
                case 'all':
                    $cmdArgs[] = implode(',', $pidList);
                    break;

                default:
                    $cmdArgs[] = $pid;
                    break;
            }

            $straceOpts = array(
                't' => '-tt',
            );

            // Stats
            if ($input->getOption('c')) {
                $straceOpts['c'] = '-c';
            }

            // Relative time
            if ($input->getOption('r')) {
                unset($straceOpts['t']);
                $straceOpts['r'] = '-r';
            }

            $straceOpts = implode(' ', $straceOpts);

            if (!empty($grep)) {
                $cmdArgs[] = $grep;
                CommandExecutionUtility::execInteractive('sudo',
                    'strace ' . $straceOpts . ' -p %s 2>&1  | grep --color=auto %s', $cmdArgs);
            } else {
                CommandExecutionUtility::execInteractive('sudo', 'strace ' . $straceOpts . ' -p %s', $cmdArgs);
            }
        }

        return 0;
    }
}
