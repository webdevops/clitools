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

use CliTools\Utility\PhpUtility;
use CliTools\Console\Shell\CommandBuilder\EditorCommandBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends \CliTools\Console\Command\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('sync:init')
            ->setDescription('Create example clisync.yml');
    }

    /**
     * Execute command
     *
     * @param  InputInterface  $input  Input instance
     * @param  OutputInterface $output Output instance
     *
     * @return int|null|void
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        $cliSyncFilePath = getcwd() . '/' . AbstractCommand::CONFIG_FILE;

        if (file_exists($cliSyncFilePath)) {
            $this->output->writeln('<p-error>Configuration file ' . AbstractCommand::CONFIG_FILE . ' already exists</p-error>');
            return 1;
        }

        $content = '# Example clisync configuration file

#######################################
# Sync from server (eg. live server)
#######################################
server:

   ##################
   # Global config
   ##################
  _:
    mysql:
        # MySQL predefined filter for typo3 (eg. no caching tables)
      filter: typo3

      # MySQL custom filter (preg_match)
      #filter:
      #  - "/^cachingframework_.*/i"
      #  - "/^cf_.*/i"
      #  - "/^cache_.*/i"
      #  - "/^index_.*/i"
      #  - "/^sys_log$/i"
      #  - "/^sys_history$/i"
      #  - "/^tx_extbase_cache.*/i"

        # Transfer compression (none if empty, bzip2 or gzip)
      compression: bzip2

        # specific mysqldump settings
      mysqldump:
        option: "--opt --single-transaction"

    rsync:
        # directory list/patterns
      directory:
        - "/fileadmin/"
        - "/uploads/"
        - "/typo3conf/l10n/"

        # directory exclude list/patterns
      exclude:
        - "/fileadmin/_processed_/**"

   ##################
   # Config "production"
   ##################
  production:
      # ssh server host or name (see .ssh/config, eg for mysql/mysqldump)
    ssh:
      hostname: live-server

      # rsync for some directories
    rsync:
        # server and source directory (server host or name - see .ssh/config)
      path: "live-server:/var/www/website/htdocs"
        # set target as sub directroy (will be appended to working directory)
      #target: "html/"

    mysql:
        # mysql connection
      hostname: localhost
      username: typo3
      password: loremipsum

        # List of databases for sync ("local:foreign" for different database names - or only "database" if same name should be used localy)
      database:
        - typo3:website_live
        - other_database

#######################################
# Shared server (sharing between developers)
#######################################
share:

  rsync:
      # source/target directory or server via ssh (eg. backup-server:/backup/projectname)
    path: "/tmp/foo/"
      # set target as sub directroy (will be appended to working directory
    #target: "html/"

      # List of directories for backup
    directory:
      - "/fileadmin/"
      - "/uploads/"
      - "/typo3conf/l10n/"

      # List of excludes (eg. specific files)
    exclude:
        # no avi files
      - "/example/**/*.avi"
        # no mp4 files
      - "/example/**/*.mp4"

  mysql:
      # MySQL filter for typo3 (eg. no caching tables)
    filter: typo3

      # List of databases for backup
    database:
      - typo3

#######################################
# Task configuration
#######################################
task:

    # These commands will be executed after backup, restore and sync
  finalize:
      # create user "dev" with password "dev"
    - "ct typo3:beuser"
      # append toplevel-domain .vm to all domains
    - "ct typo3:domain"
';

        PhpUtility::filePutContents($cliSyncFilePath, $content);

        // Start editor with file (if $EDITOR is set)
        try {
            $editor = new EditorCommandBuilder();
            $editor
                ->addArgument($cliSyncFilePath)
                ->executeInteractive();
        } catch (\Exception $e) {
            $this->output->writeln('<p-error>' . $e->getMessage() . '</p-error>');
        }

        $this->output->writeln('<info>Successfully created ' . AbstractCommand::CONFIG_FILE . ' </info>');
    }

}
