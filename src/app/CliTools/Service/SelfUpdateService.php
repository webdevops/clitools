<?php

namespace CliTools\Service;

/**
 * CliTools Command
 * Copyright (C) 2014 Markus Blaschke <markus@familie-blaschke.net>
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
     */
    protected $updateUrl = 'https://www.achenar.net/clicommand/clitools.phar';

    /**
     * Path to current clitools command
     *
     * @var null|string
     */
    protected $cliToolsCommandPath = null;

    /**
     * Permissions
     *
     * @var array
     */
    protected $cliToolsCommandPerms = array();

    /**
     * Update path
     *
     * @var null
     */
    protected $cliToolsUpdatePath = null;

    /**
     * @var null|\Symfony\Component\Console\Output\OutputInterface
     */
    protected $output = null;

    /**
     * @var null|\CliTools\Console\Application
     */
    protected $application = null;

    public function __construct($app, $output) {
        // FIXME: Dependency injection
        $this->application = $app;
        $this->output = $output;
    }

    /**
     * Update clitools command
     */
    public function update() {
        $this->updateUrl = $this->application->getConfigValue('config', 'self_update_url', null);

        if (empty($this->updateUrl)) {
            throw new \RuntimeException('Self-Update url is not set');
        }

        $this->output->writeln('<info>Collecting informations...</info>');
        $this->collectInformations();

        $this->output->writeln('<info>Update URL: ' . $this->updateUrl . '</info>');

        $this->output->writeln('<info>Download new clitools command version...</info>');
        $this->downloadUpdate();

        $this->output->writeln('<info>Deploy update...</info>');
        $this->deployUpdate();

        $this->cleanup();
    }

    /**
     * Get current file informations=
     */
    protected function collectInformations() {
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
        $this->cliToolsCommandPerms['owner'] = fileowner($this->cliToolsCommandPath);
        $this->cliToolsCommandPerms['group'] = filegroup($this->cliToolsCommandPath);
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

        $tmpFile = tempnam(sys_get_temp_dir(), 'upg');
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

        // Apply owner
        chown($this->cliToolsCommandPath, $this->cliToolsCommandPerms['owner']);

        // Apply group
        chgrp($this->cliToolsCommandPath, $this->cliToolsCommandPerms['group']);

        // Apply perms
        chmod($this->cliToolsCommandPath, $this->cliToolsCommandPerms['perms']);
    }

    /**
     * Cleanup
     */
    protected function cleanup() {

    }
}