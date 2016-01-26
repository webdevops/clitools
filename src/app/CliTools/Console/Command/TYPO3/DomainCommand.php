<?php

namespace CliTools\Console\Command\TYPO3;

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

use CliTools\Database\DatabaseConnection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DomainCommand extends \CliTools\Console\Command\AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('typo3:domain')
             ->setDescription('Add common development domains to database')
             ->addArgument(
                 'db',
                 InputArgument::OPTIONAL,
                 'Database name'
             )
             ->addOption(
                 'baseurl',
                 null,
                 InputOption::VALUE_NONE,
                 'Also set config.baseURL setting'
             )
             ->addOption(
                 'list',
                 null,
                 InputOption::VALUE_NONE,
                 'List only databases'
             )
             ->addOption(
                 'remove',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'Remove domain (with wildcard support)'
             )
             ->addOption(
                 'duplicate',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'Add duplication domains (will duplicate all domains in system, eg. for vagrant share)'
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
        // ##################
        // Init
        // ##################
        $dbName = $input->getArgument('db');

        $output->writeln('<h2>Updating TYPO3 domain entries</h2>');

        // ##############
        // Loop through databases
        // ##############

        if (empty($dbName)) {
            // ##############
            // All databases
            // ##############
            $databaseList = DatabaseConnection::databaseList();

            foreach ($databaseList as $dbName) {
                // Check if database is TYPO3 instance
                $query = 'SELECT COUNT(*) as count
                            FROM information_schema.tables
                           WHERE table_schema = ' . DatabaseConnection::quote($dbName) . '
                             AND table_name = \'sys_domain\'';
                $isTypo3Database = DatabaseConnection::getOne($query);

                if ($isTypo3Database) {
                    $this->runTaskForDomain($dbName);
                }
            }
        } else {
            // ##############
            // One databases
            // ##############
            $this->runTaskForDomain($dbName);
        }
    }

    /**
     * Run tasks for one domain
     */
    protected function runTaskForDomain($dbName)
    {
        DatabaseConnection::switchDatabase($dbName);

        if ($this->input->getOption('list')) {
            // Show domain list (and skip all other tasks)
            $this->showDomainList($dbName);
        } else {
            // Remove domains (eg. for cleanup)
            if ($this->input->getOption('remove')) {
                $this->removeDomains($this->input->getOption('remove'));
            }

            // Set development domains
            $this->manipulateDomains();

            // Add sharing domains
            if ($this->input->getOption('baseurl')) {
                $this->updateBaseUrlConfig();
            }

            // Add sharing domains
            if ($this->input->getOption('duplicate')) {
                $this->addDuplicateDomains($this->input->getOption('duplicate'));
            }

            // Show domain list
            $this->showDomainList($dbName);
        }
    }

    /**
     * Remove domains
     */
    protected function removeDomains($pattern)
    {
        $pattern = str_replace('*', '%', $pattern);

        $query = 'DELETE FROM sys_domain WHERE domainName LIKE %s';
        $query = sprintf($query, DatabaseConnection::quote($pattern));
        DatabaseConnection::exec($query);
    }

    /**
     * Update baseURL config
     */
    protected function updateBaseUrlConfig()
    {
        $query = 'SELECT st.uid as template_id,
                         st.config as template_config,
                         (SELECT sd.domainName
                            FROM sys_domain sd
                           WHERE sd.pid = st.pid
                             AND sd.hidden = 0
                        ORDER BY sd.forced DESC,
                                 sd.sorting ASC
                           LIMIT 1) as domain_name
                    FROM sys_template st
                   WHERE st.root = 1
                     AND st.deleted  = 0
                  HAVING domain_name IS NOT NULL';
        $templateIdList = DatabaseConnection::getAll($query);

        foreach ($templateIdList as $row) {
            $templateId   = $row['template_id'];
            $domainName   = $row['domain_name'];
            $templateConf = $row['template_config'];

            // Remove old baseURL entries (no duplciates)
            $templateConf = preg_replace('/^config.baseURL = .*$/m', '', $templateConf);
            $templateConf = trim($templateConf);

            // Add new baseURL
            $templateConf .= "\n" . 'config.baseURL = http://' . $domainName . '/';

            $query = 'UPDATE sys_template SET config = %s WHERE uid = %s';
            $query = sprintf($query, DatabaseConnection::quote($templateConf), (int)$templateId);
            DatabaseConnection::exec($query);
        }
    }

    /**
     * Add share domains (eg. for vagrantshare)
     *
     * @param string $suffix Domain suffix
     */
    protected function addDuplicateDomains($suffix)
    {
        $devDomain = '.' . $this->getApplication()
                                ->getConfigValue('config', 'domain_dev');

        $query      = 'SELECT * FROM sys_domain';
        $domainList = DatabaseConnection::getAll($query);

        foreach ($domainList as $domain) {
            unset($domain['uid']);

            $domainName = $domain['domainName'];

            // remove development suffix
            $domainName = preg_replace('/' . preg_quote($devDomain) . '$/', '', $domainName);

            // add share domain
            $domainName .= '.' . ltrim($suffix, '.');

            $domain['domainName'] = $domainName;

            DatabaseConnection::insert('sys_domain', $domain);
        }
    }

    /**
     * Show list of domains
     *
     * @param string $dbName Domain name
     */
    protected function showDomainList($dbName)
    {
        $query = 'SELECT domainName
                    FROM sys_domain
                   WHERE hidden = 0
                ORDER BY domainName ASC';
        $domainList = DatabaseConnection::getCol($query);

        $this->output->writeln('<p>Domain list of "' . $dbName . '":</p>');

        foreach ($domainList as $domain) {
            $this->output->writeln('<p>  ' . $domain . '</p>');
        }
        $this->output->writeln('');
    }

    /**
     * Set development domains for TYPO3 database
     *
     * @return void
     */
    protected function manipulateDomains()
    {
        $devDomain    = '.' . $this->getApplication()
                                   ->getConfigValue('config', 'domain_dev');
        $domainLength = strlen($devDomain);

        // ##################
        // Fix domains
        // ##################
        $query = 'UPDATE sys_domain
                     SET domainName = CONCAT(domainName, ' . DatabaseConnection::quote($devDomain) . ')
                   WHERE RIGHT(domainName, ' . $domainLength . ') <> ' . DatabaseConnection::quote($devDomain);
        DatabaseConnection::exec($query);
    }
}
