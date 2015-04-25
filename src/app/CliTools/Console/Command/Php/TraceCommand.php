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

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use CliTools\Console\Builder\CommandBuilder;

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
                'all',
                null,
                InputOption::VALUE_NONE,
                'Trace all PHP processes'
            )
            ->addOption(
                'e',
                'e',
                InputOption::VALUE_REQUIRED,
                'System call filter'
            )
            ->addOption(
                'c',
                'c',
                InputOption::VALUE_NONE,
                'SysCall statistics'
            )
            ->addOption(
                'r',
                'r',
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
        $this->elevateProcess($input, $output);

        $pid        = null;
        $grep       = $input->getArgument('grep');

        $command = new CommandBuilder('strace');
        $command->setOutputRedirect(CommandBuilder::OUTPUT_REDIRECT_ALL_STDOUT);


        if (empty($pid)) {
            list($pidList, $phpProcessList) = $this->buildPhpProcessList();

            if ($input->getOption('all')) {
                $pid = 'all';
            } else {
                $question = new ChoiceQuestion('Please choose PHP process for tracing', $phpProcessList);

                $questionDialog = new QuestionHelper();

                $pid = $questionDialog->ask($input, $output, $question);
            }
        }


        if (!empty($pid)) {
            switch ($pid) {
                case 'all':
                    $command->addArgumentTemplate('-p %s', implode(',', $pidList));
                    break;

                default:
                    $command->addArgumentTemplate('-p %s', $pid);
                    break;
            }

            // Stats
            if ($input->getOption('c')) {
                $command->addArgument('-c');
            }

            // Relative time
            if ($input->getOption('r')) {
                $command->addArgument('-r');
            } else {
                $command->addArgument('-tt');
            }

            // System trace filter
            if ($input->getOption('e')) {
                $command->addArgumentTemplate('-e %s', $input->getOption('e'));
            }

            // Add grep
            if (!empty($grep)) {
                $grepCommand = new CommandBuilder('grep');
                $grepCommand->addArgument('--color=auto')
                    ->addArgument($grep);

                $command->addPipeCommand($grepCommand);
            }

            $command->executeInteractive();
        }

        return 0;
    }

    /**
     * Build list of running php processes
     *
     * @return array
     */
    protected function buildPhpProcessList() {
        $currentPid = posix_getpid();

        $phpProcessList = array(
            'all PHP processes' => 'all',
        );


        $command = new CommandBuilder('ps');
        $command->addArgumentRaw('h -o pid,comm,args -C php5-fpm,php-fpm,php5,php');
        $cmdOutput = $command->execute()->getOutput();

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

        return array($pidList, $phpProcessList);
    }
}
