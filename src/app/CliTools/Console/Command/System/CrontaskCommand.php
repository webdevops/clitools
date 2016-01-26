<?php

namespace CliTools\Console\Command\System;

/*
 * CliTools Command
 * Copyright (C) 2016 WebDevOps.io
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

use CliTools\Shell\CommandBuilder\SelfCommandBuilder;
use CliTools\Utility\UnixUtility;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrontaskCommand extends \CliTools\Console\Command\AbstractCommand implements
    \CliTools\Console\Filter\OnlyRootFilterInterface
{

    /**
     * List of warning messages
     *
     * @var array
     */
    protected $sysCheckMessageList = array();

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('system:crontask')
             ->setDescription('System cron task');
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
        $this->setupBanner();

        if ($this->getApplication()
                 ->getConfigValue('syscheck', 'enabled', true)
        ) {
            $this->systemCheck();
        }

        return 0;
    }

    /**
     * Setup banner
     */
    protected function setupBanner()
    {
        $command = new SelfCommandBuilder();
        $command->addArgument('system:banner');
        $output = $command->execute()
                          ->getOutputString();

        // escape special chars for /etc/issue
        $outputIssue = addcslashes($output, '\\');

        file_put_contents('/etc/issue', $outputIssue);
        file_put_contents('/etc/motd', $output);

        UnixUtility::reloadTtyBanner('tty1');
    }

    /**
     * Check system health
     */
    protected function systemCheck()
    {
        $this->systemCheckDiskUsage();

        if (!empty($this->sysCheckMessageList)) {
            if ($this->getApplication()
                     ->getConfigValue('syscheck', 'growl', 0)
            ) {
                // Growl notification
                $message = 'WARNING:' . "\n\n" . implode("\n", $this->sysCheckMessageList);
                $this->sendGrowlMessage('CliTools :: System Check Warnings', $message);
            }

            if ($this->getApplication()
                     ->getConfigValue('syscheck', 'wall', 0)
            ) {
                // Local wall message
                $msgPrefix = ' - ';
                $message   = ' -- CliTools :: System Check Warnings --' . "\n\n";
                $message .= $msgPrefix . implode("\n" . $msgPrefix, $this->sysCheckMessageList);
                $message .= "\n\n" . '(This warning can be disabled in /etc/clitools.ini)';
                UnixUtility::sendWallMessage($message);
            }
        }
    }

    /**
     * Send growl message
     *
     * @param string $title   Notification title
     * @param string $message Notification message
     */
    protected function sendGrowlMessage($title, $message)
    {
        require CLITOOLS_ROOT_FS . '/vendor/jamiebicknell/Growl-GNTP/growl.gntp.php';

        $growlServer   = (string)$this->getApplication()
                                      ->getConfigValue('growl', 'server', null);
        $growlPassword = (string)$this->getApplication()
                                      ->getConfigValue('growl', 'password', null);

        if (!empty($growlServer)) {
            $growl = new \Growl($growlServer, $growlPassword);
            $growl->setApplication('Vagrant VM', 'Vagrant Development VM');

            // Only need to use the following method on first use or change of icon
            $growl->registerApplication();

            // Basic Notification
            $growl->notify($title, $message);
        }
    }

    /**
     * Check system disk usage
     */
    protected function systemCheckDiskUsage()
    {
        $diskUsageLimit = abs(
            $this->getApplication()
                 ->getConfigValue('syscheck', 'diskusage', 0)
        );

        if (!empty($diskUsageLimit)) {
            $mountInfoList = UnixUtility::mountInfoList();
            foreach ($mountInfoList as $mount => $stats) {
                $usageInt = $stats['usageInt'];

                $statsLine = array(
                    $usageInt . '% used',
                    \CliTools\Utility\FormatUtility::bytes($stats['free']) . ' free',
                );

                if ($usageInt >= $diskUsageLimit) {
                    $this->sysCheckMessageList[] = 'Mount "' . $mount . '" exceeds limit of ' . $diskUsageLimit . '% (' . implode(
                            ', ',
                            $statsLine
                        ) . ')';
                }
            }
        }
    }
}
