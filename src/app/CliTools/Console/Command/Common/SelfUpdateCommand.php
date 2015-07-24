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

use CliTools\Service\SelfUpdateService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommand extends \CliTools\Console\Command\AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('self-update')
             ->setAliases(array('selfupdate'))
             ->setDescription(
                 'Self update of CliTools Command'
             )
             ->addOption(
                 'force',
                 'f',
                 InputOption::VALUE_NONE,
                 'Force update'
             )
             ->addOption(
                 'beta',
                 null,
                 InputOption::VALUE_NONE,
                 'Allow update to beta releases'
             )
             ->addOption(
                 'fallback',
                 null,
                 InputOption::VALUE_NONE,
                 'Fallback to old update url'
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
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $force = (bool)$input->getOption('force');

        $updateService = new SelfUpdateService($this->getApplication(), $output);

        if ($input->getOption('beta')) {
            $updateService->enablePreVersions();
        }

        if ($input->getOption('fallback')) {
            $updateService->enableUpdateFallback();
        }

        // Check if we need root rights
        if (!$this->getApplication()
                  ->isRunningAsRoot() && $updateService->isElevationNeeded()
        ) {
            $this->elevateProcess($input, $output);
        }

        $updateService->update($force);
    }
}
