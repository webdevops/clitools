<?php

namespace CliTools\Service;

use CliTools\Console\Builder\CommandBuilder;

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

class SelfUpdateService {

    /**
     * Update url
     *
     * @var null|string
     */
    protected $updateUrl;

    /**
     * Path to current clitools command
     *
     * @var null|string
     */
    protected $cliToolsCommandPath;

    /**
     * Permissions
     *
     * @var array
     */
    protected $cliToolsCommandPerms = array();

    /**
     * Update path
     *
     * @var null|string
     */
    protected $cliToolsUpdatePath;

    /**
     * @var null|\Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var null|\CliTools\Console\Application
     */
    protected $application;

    /**
     * Constructor
     *
     * @param $app
     * @param $output
     */
    public function __construct($app, $output) {
        $this->application = $app;
        $this->output      = $output;

        $this->collectInformations();
    }

    /**
     * Check if super user rights are required
     *
     * @return boolean
     */
    public function isElevationNeeded() {
        $ret = false;

        if (posix_getuid() !== $this->cliToolsCommandPerms['owner']) {
            $ret = true;
        }

        return $ret;
    }

    /**
     * Update clitools command
     */
    public function update() {
        $this->updateUrl = $this->application->getConfigValue('config', 'self_update_url', null);

        if (empty($this->updateUrl)) {
            throw new \RuntimeException('Self-Update url is not set');
        }

        $this->output->writeln('<info>Update URL: ' . $this->updateUrl . '</info>');

        $this->output->writeln('<info>Download new clitools command version...</info>');
        $this->downloadUpdate();

        try {
            $versionString = $this->testUpdate();

            $this->output->writeln('<info>Deploy update...</info>');
            $this->deployUpdate();

            $this->output->writeln('');
            $this->output->writeln('<info>Updated to:</info>');
            $this->output->writeln('   ' . $versionString);
            $this->output->writeln('');
        } catch (\Exception $e) {
            $this->output->writeln('<error>Update failed</error>');
        }

        $this->cleanup();
    }

    /**
     * Get current file informations=
     */
    protected function collectInformations() {
        $this->output->writeln('<info>Collecting informations...</info>');

        // ##################
        // Current path
        // ##################

        $path = \Phar::running(false);

        if (empty($path)) {
            throw new \RuntimeException('Self-Update only supported in PHAR-mode');
        }

        $this->cliToolsCommandPath = $path;

        // ##################
        // Get perms
        // ##################
        $this->cliToolsCommandPerms['perms'] = fileperms($this->cliToolsCommandPath);
        $this->cliToolsCommandPerms['owner'] = (int)fileowner($this->cliToolsCommandPath);
        $this->cliToolsCommandPerms['group'] = (int)filegroup($this->cliToolsCommandPath);
    }

    /**
     * Download file
     */
    protected function downloadUpdate() {
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $this->updateUrl);
        curl_setopt($curlHandle, CURLOPT_VERBOSE, 0);
        curl_setopt($curlHandle, CURLOPT_HEADER, 0);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, 1);

        $curlData = curl_exec($curlHandle);
        if (curl_errno($curlHandle) || empty($curlData)) {
            throw new \RuntimeException('Could not download update: ' . curl_error($curlHandle));
        }
        curl_close($curlHandle);

        $tmpFile = tempnam(sys_get_temp_dir(), 'ct');
        file_put_contents($tmpFile, $curlData);

        $this->cliToolsUpdatePath = $tmpFile;
    }

    /**
     * Deploy update
     */
    protected function deployUpdate() {

        // ##################
        // Backup
        // ##################

        $backupPath = $this->cliToolsCommandPath . '.bak';
        $this->output->writeln('<comment>   Backup current version to ' . $backupPath . '</comment>');
        if (is_file($backupPath)) {
            unlink($backupPath);
        }
        rename($this->cliToolsCommandPath, $backupPath);

        // ##################
        // Deploy
        // ##################

        $this->output->writeln('<comment>   Move new version to ' . $this->cliToolsCommandPath . '</comment>');

        // Move to current location
        rename($this->cliToolsUpdatePath, $this->cliToolsCommandPath);

        if ($this->application->isRunningAsRoot()) {
            // Apply owner
            chown($this->cliToolsCommandPath, $this->cliToolsCommandPerms['owner']);

            // Apply group
            chgrp($this->cliToolsCommandPath, $this->cliToolsCommandPerms['group']);
        }

        // Apply perms
        chmod($this->cliToolsCommandPath, $this->cliToolsCommandPerms['perms']);
    }

    /**
     * Test update and show version
     *
     * @return string
     */
    protected function testUpdate() {
        $command = new CommandBuilder('php');
        $ret = $command->addArgument($this->cliToolsUpdatePath)
            ->addArgument('--version')
            ->addArgument('--no-ansi')
            ->execute()->getOutputString();
        return $ret;
    }

    /**
     * Cleanup
     */
    protected function cleanup() {
    }
}
