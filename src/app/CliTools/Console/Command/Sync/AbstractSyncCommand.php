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

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;

abstract class AbstractSyncCommand extends \CliTools\Console\Command\Sync\AbstractCommand {
    
    /**
     * Config area
     *
     * @var string
     */
    protected $confArea = 'server';


    /**
     * Validate configuration
     *
     * @return boolean
     */
    protected function validateConfiguration() {
        $ret = parent::validateConfiguration();

        $output = $this->output;

        // ##################
        // SSH (optional)
        // ##################

        if ($this->config->exists('ssh')) {
            // Check if one database is configured
            if (!$this->config->exists('ssh.hostname')) {
                $output->writeln('<error>No ssh hostname configuration found</error>');
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     * Get server context from user
     */
    protected function getServerContext() {
        $ret = null;

        if (!$this->input->getArgument('context')) {
            // ########################
            // Ask user for server context
            // ########################

            $serverList = $this->config->getList();
            $serverList = array_diff($serverList, array('_'));

            if (empty($serverList)) {
                throw new \RuntimeException('No valid servers found in configuration');
            }

            $questionList = array_combine(array_values($serverList), array_values($serverList) );
            $question = new ChoiceQuestion('Please choose process for tracing', $questionList);
            $question->setMaxAttempts(1);

            $questionDialog = new QuestionHelper();

            $ret = $questionDialog->ask($this->input, $this->output, $question);
        } else {
            $ret = $this->input->getArgument('context');
        }

        return $ret;
    }


}
