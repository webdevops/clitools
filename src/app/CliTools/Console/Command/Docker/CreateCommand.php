<?php

namespace CliTools\Console\Command\Docker;

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

use CliTools\Shell\CommandBuilder\CommandBuilder;
use CliTools\Shell\CommandBuilder\EditorCommandBuilder;
use CliTools\Shell\CommandBuilder\SelfCommandBuilder;
use CliTools\Utility\PhpUtility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('docker:create')
             ->setDescription('Create new docker boilerplate')
             ->addArgument(
                 'path',
                 InputArgument::REQUIRED,
                 'Directory for new docker boilerplate instance'
             )
             ->addOption(
                 'docker',
                 'd',
                 InputOption::VALUE_REQUIRED,
                 'Docker Boilerplate repository'
             )
             ->addOption(
                 'code',
                 'c',
                 InputOption::VALUE_REQUIRED,
                 'Code repository'
             )
             ->addOption(
                 'make',
                 'm',
                 InputOption::VALUE_REQUIRED,
                 'Makefile command'
             )
             ->addOption(
                 'up',
                 null,
                 InputOption::VALUE_NONE,
                 'Build and start docker containers'
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
        $currDir = getcwd();

        $path            = $input->getArgument('path');
        $boilerplateRepo = $this->getBoilerplateRepository();
        $codeRepo        = $this->input->getOption('code');


        $output->writeln('<h2>Creating new docker boilerplate instance in "' . $path . '"</h2>');
        $output->writeln('<p> Docker repository: ' . $boilerplateRepo . '</p>');
        if ($codeRepo) {
            $output->writeln('<p>   Code repository: ' . $codeRepo . '</p>');
        }

        // Init docker boilerplate
        $this->createDockerInstance($path, $boilerplateRepo);
        PhpUtility::chdir($currDir);

        // Init code
        if ($codeRepo) {

            $output->writeln('<h2>Init code repository</h2>');
            $this->initCode($path, $codeRepo);
            PhpUtility::chdir($currDir);

            $output->writeln('<h2>Init document root</h2>');
            // detect document root
            $this->initDocumentRoot($path);
            PhpUtility::chdir($currDir);

            // Run makefile
            if ($this->input->getOption('make')) {
                try {
                    $output->writeln('<h2>Run Makefile</h2>');
                    $this->runMakefile($path, $input->getOption('make'));
                    PhpUtility::chdir($currDir);
                } catch (\Exception $e) {
                    $this->addFinishMessage('<p-error>Make command failed: ' . $e->getMessage() . '</p-error>');
                }
            }
        }

        // Start interactive editor
        $this->startInteractiveEditor($path . '/docker-compose.yml');
        $this->startInteractiveEditor($path . '/docker-env.yml');

        // Start docker
        if ($this->input->getOption('up')) {
            $output->writeln('<h2>Build and start docker containers</h2>');
            PhpUtility::chdir($currDir);
            $this->startDockerInstance($path);
        }

        return 0;
    }

    /**
     * Get boilerplate repository
     *
     * @return mixed|null
     */
    protected function getBoilerplateRepository() {
        $app = $this->getApplication();

        // Boilerplate from config (legacy default)
        $ret = $app->getConfigValue('docker', 'boilerplate');

        // Boilerplate from config (default)
        if (empty($ret)) {
            $ret = $app->getConfigValue('dockerBoilerplate', 'default');
        }

        // Boilerplate from config (type)
        if ($this->input->getOption('docker')) {
            $ret = $app ->getConfigValue('dockerBoilerplate', $this->input->getOption('docker'));

            if (empty($ret)) {
                // Custom boilerplate
                $ret = $this->input->getOption('docker');
            }
        }

        return $ret;
    }

    /**
     * Start interactive editor
     *
     * @param string $path Path to file
     */
    protected function startInteractiveEditor($path)
    {
        if (file_exists($path)) {
            // Start editor with file (if $EDITOR is set)
            try {
                $editor = new EditorCommandBuilder();

                $this->setTerminalTitle('Edit', basename($path));

                $this->output->writeln('<h2>Starting interactive EDITOR for file ' . $path . '</h2>');
                sleep(1);

                $editor->addArgument($path)
                       ->executeInteractive();
            } catch (\Exception $e) {
                $this->addFinishMessage('<p-error>' . $e->getMessage() . '</p-error>');
            }
        }
    }

    /**
     * Create docker instance from git repository
     *
     * @param string $path Path
     * @param string $repo Repository
     */
    protected function createDockerInstance($path, $repo)
    {
        $this->setTerminalTitle('Cloning docker');

        $command = new CommandBuilder('git', 'clone --branch=master --recursive %s %s', array($repo, $path));
        $command->executeInteractive();
    }

    /**
     * Create docker instance from git repository
     *
     * @param string $path Path
     * @param string $repo Repository
     */
    protected function initCode($path, $repo)
    {
        $this->setTerminalTitle('Cloning code');

        $path .= '/code';

        $this->output->writeln('<comment>Initialize new code instance in "' . $path . '"</comment>');

        if (is_dir($path)) {
            if (file_exists($path . '/.gitkeep')) {
                // Remove gitkeep
                PhpUtility::unlink($path . '/.gitkeep');
            }

            // Remove code directory
            $command = new CommandBuilder('rmdir');
            $command->addArgumentSeparator()
                    ->addArgument($path)
                    ->executeInteractive();
        }

        $command = new CommandBuilder('git', 'clone --branch=master --recursive %s %s', array($repo, $path));
        $command->executeInteractive();
    }


    /**
     * Create docker instance from git repository
     *
     * @param string $path Path
     */
    protected function initDocumentRoot($path)
    {
        $codePath      = $path . '/code';
        $dockerEnvFile = $path . '/docker-env.yml';

        $documentRoot = null;

        // try to detect document root
        if (is_dir($codePath . '/html')) {
            $documentRoot = 'code/html';
        } elseif (is_dir($codePath . '/htdocs')) {
            $documentRoot = 'code/htdocs';
        } elseif (is_dir($codePath . '/Web')) {
            $documentRoot = 'code/Web';
        } elseif (is_dir($codePath . '/web')) {
            $documentRoot = 'code/web';
        }

        if ($documentRoot && is_file($dockerEnvFile)) {
            $dockerEnv = PhpUtility::fileGetContentsArray($dockerEnvFile);

            unset($line);
            foreach ($dockerEnv as &$line) {
                $line = preg_replace(
                    '/^[\s]*DOCUMENT_ROOT[\s]*=code\/?[\s]*$/ms',
                    'DOCUMENT_ROOT=' . $documentRoot,
                    $line
                );
            }
            unset($line);

            $dockerEnv = implode("\n", $dockerEnv);

            PhpUtility::filePutContents($dockerEnvFile, $dockerEnv);
        }
    }

    /**
     * Run make task
     *
     * @param string $path        Path of code
     * @param string $makeCommand Makefile command
     */
    protected function runMakefile($path, $makeCommand)
    {
        $this->setTerminalTitle('Run make');

        $path .= '/code';

        $this->output->writeln('<comment>Running make with command "' . $makeCommand . '"</comment>');
        try {
            PhpUtility::chdir($path);

            // Remove code directory
            $command = new CommandBuilder('make');
            $command->addArgument($makeCommand)
                    ->executeInteractive();
        } catch (\Exception $e) {
            $this->addFinishMessage('<p-error>Make command failed: ' . $e->getMessage() . '</p-error>');
        }
    }

    /**
     * Build and startup docker instance
     *
     * @param string $path Path
     */
    protected function startDockerInstance($path)
    {
        $this->setTerminalTitle('Start docker');

        $this->output->writeln('<comment>Building docker containers "' . $path . '"</comment>');

        PhpUtility::chdir($path);

        $command = new SelfCommandBuilder();
        $command->addArgument('docker:up');
        $command->executeInteractive();
    }

}
