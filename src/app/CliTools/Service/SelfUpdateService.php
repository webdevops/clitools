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
     * Version
     *
     * @var null|string
     */
    protected $updateVersion;

    /**
     * Changelog
     *
     * @var null|string
     */
    protected $updateChangelog;

    /**
     * Update github url
     *
     * @var null|string
     */
    protected $githubReleaseUrl;

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
     *
     * @param boolean $force Force update
     */
    public function update($force = false) {
        $this->githubReleaseUrl = $this->application->getConfigValue('config', 'self_update_github', null);

        if (!empty($this->githubReleaseUrl)) {
            $this->fetchLatestReleaseFromGithub();
        } else {
            throw new \RuntimeException('GitHub Release URL not set');
        }

        if ($this->checkIfUpdateNeeded($force)) {
            // Update needed
            $this->doUpdate();
        }
    }

    /**
     * Check if update is needed
     *
     * @param boolean $force Force update
     *
     * @return bool
     */
    protected function checkIfUpdateNeeded($force) {
        $ret = false;

        $this->output->write('<comment>Checking version... </comment>');

        // Check if version is equal
        if ($this->updateVersion !== CLITOOLS_COMMAND_VERSION) {
            $this->output->write('<comment>new version "' . $this->updateVersion . '" found</comment>');
            $ret = true;
        } else {
            $this->output->write('<comment>already up to date</comment>');
        }

        // Check if update is forced
        if ($force) {
            $this->output->write('<info> [forced]</info>');
            $ret = true;
        }

        $this->output->writeln('');

        return $ret;
    }

    /**
     * Do update
     */
    protected function doUpdate() {
        if (empty($this->updateUrl)) {
            throw new \RuntimeException('Self-Update url is not found');
        }

        $this->output->writeln('<info>Update URL: ' . $this->updateUrl . '</info>');

        $this->output->write('<info>Downloading.</info>');
        $this->downloadUpdate();
        $this->output->writeln('<info> done</info>');

        try {
            // Test update
            $versionString = $this->testUpdate();

            // Deploy update
            $this->output->writeln('<info>Deploying update... </info>');
            $this->deployUpdate();

            // Show version
            $this->output->writeln('');
            $this->output->writeln('<info>Updated to:</info>');
            $this->output->writeln('   ' . $versionString);
            $this->output->writeln('');

            // Show changelog
            if (!empty($this->updateChangelog)) {
                $this->showChangelog();
            }

        } catch (\Exception $e) {
            $this->output->writeln('<error>Update failed</error>');
        }

        $this->cleanup();
    }

    /**
     * Fetch latest release from github api
     */
    protected function fetchLatestReleaseFromGithub() {
        $this->output->write('<info>Getting informations from GitHub... </info>');

        $data = \CliTools\Utility\PhpUtility::curlFetch($this->githubReleaseUrl);
        $data = json_decode($data, true);

        if (!empty($data)) {
            // Check release
            if (!empty($data['draft']) || !empty($data['prerelease'])) {
                throw new \RuntimeException('Fetched release of GitHub was not valid (either draft or prerelease)');
            }

            // Check for required tag_name
            if (empty($data['tag_name'])) {
                throw new \RuntimeException('Fetched release of GitHub was not valid (tag_name is empty)');
            }

            // Get basic informations
            $this->updateVersion   = trim($data['tag_name']);
            $this->updateChangelog = $data['body'];

            foreach ($data['assets'] as $asset) {
                if ($asset['name'] === 'clitools.phar') {
                    $this->updateUrl = $asset['browser_download_url'];
                }
            }
        }

        $this->output->writeln('<info>done</info>');
    }

    /**
     * Show changelog
     */
    protected function showChangelog() {

        $message = $this->updateChangelog;

        // Pad lines
        $message = explode("\n", $message);
        $message = array_map(function($line) {
            return '  ' . $line;
        }, $message);
        $message = implode("\n", $message);

        $message = preg_replace('/`([^`]+)`/', '<comment>\1</comment>', $message);

        $this->output->writeln('<info>Changelog:</info>');
        $this->output->writeln($message);
        $this->output->writeln('');
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
        $output = $this->output;

        // Progress counter
        $progress = function($downloadTotal, $downoadProgress) use ($output) {
            static $counter = 0;

            if($counter % 30 === 0) {
                $output->write('<info>.</info>');
            }

            $counter++;
        };

        $data = \CliTools\Utility\PhpUtility::curlFetch($this->updateUrl, $progress);

        $tmpFile = tempnam(sys_get_temp_dir(), 'ct');
        file_put_contents($tmpFile, $data);

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
