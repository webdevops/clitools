<?php

namespace CliTools\Console\Command\Sync;

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
use CliTools\Reader\ConfigReader;
use CliTools\Shell\CommandBuilder\CommandBuilder;
use CliTools\Shell\CommandBuilder\CommandBuilderInterface;
use CliTools\Shell\CommandBuilder\OutputCombineCommandBuilder;
use CliTools\Shell\CommandBuilder\RemoteCommandBuilder;
use CliTools\Shell\CommandBuilder\SelfCommandBuilder;
use CliTools\Utility\ConsoleUtility;
use CliTools\Utility\FilterUtility;
use CliTools\Utility\PhpUtility;
use CliTools\Utility\UnixUtility;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractCommand extends \CliTools\Console\Command\AbstractCommand
{

    const CONFIG_FILE = 'clisync.yml';
    const GLOBAL_KEY  = 'GLOBAL';

    /**
     * Config area
     *
     * @var string
     */
    protected $confArea;

    /**
     * Project working path
     *
     * @var string|boolean|null
     */
    protected $workingPath;

    /**
     * Project configuration file path
     *
     * @var string|boolean|null
     */
    protected $confFilePath;

    /**
     * Temporary storage dir
     *
     * @var string|null
     */
    protected $tempDir;

    /**
     * Configuration
     *
     * @var ConfigReader
     */
    protected $config = array();

    /**
     * Context configuration
     *
     * @var ConfigReader
     */
    protected $contextConfig = array();

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setDescription('Sync files and database from server')
             ->addArgument(
                 'context',
                 InputArgument::OPTIONAL,
                 'Configuration name for server'
             )
             ->addOption(
                 'mysql',
                 null,
                 InputOption::VALUE_NONE,
                 'Run only mysql'
             )
             ->addOption(
                 'rsync',
                 null,
                 InputOption::VALUE_NONE,
                 'Run only rsync'
             )
             ->addOption(
                 'config',
                 null,
                 InputOption::VALUE_NONE,
                 'Show generated config'
             );
    }

    /**
     * Initializes the command just after the input has been validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @throws \RuntimeException
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->initializeConfiguration();
    }

    /**
     * Init configuration
     */
    protected function initializeConfiguration()
    {
        // Search for configuration in path
        $this->findConfigurationInPath();

        // Read configuration
        $this->readConfiguration();
    }

    /**
     * Validate configuration
     *
     * @return boolean
     */
    protected function validateConfiguration()
    {
        $ret = true;

        // Rsync (optional)
        if ($this->contextConfig->exists('rsync.path')) {
            if (!$this->validateConfigurationRsync()) {
                $ret = false;
            }
        } else {
            // Clear rsync if any options set
            $this->contextConfig->clear('rsync');
        }

        // MySQL (optional)
        if ($this->contextConfig->exists('mysql.database')) {
            if (!$this->validateConfigurationMysql()) {
                $ret = false;
            }
        } else {
            // Clear mysql if any options set
            $this->contextConfig->clear('mysql');
        }

        return $ret;
    }

    /**
     * Find configuration file in current path
     */
    protected function findConfigurationInPath()
    {
        $confFileList = array(
            self::CONFIG_FILE,
            '.' . self::CONFIG_FILE,
        );

        // Find configuration file
        $this->confFilePath = UnixUtility::findFileInDirectortyTree($confFileList);
        if (empty($this->confFilePath)) {
            $this->output->writeln('<p-error>No ' . self::CONFIG_FILE . ' found in tree</p-error>');
            throw new \CliTools\Exception\StopException(1);
        }

        $this->workingPath = dirname($this->confFilePath);

        $this->output->writeln(
            '<comment>Found ' . self::CONFIG_FILE . ' directory: ' . $this->workingPath . '</comment>'
        );
    }

    /**
     * Read and validate configuration
     */
    protected function readConfiguration()
    {
        $this->config = new ConfigReader();

        if (empty($this->confArea)) {
            throw new \RuntimeException('Config area not set, cannot continue');
        }

        if (!file_exists($this->confFilePath)) {
            throw new \RuntimeException('Config file "' . $this->confFilePath . '" not found');
        }

        $conf = Yaml::parse(PhpUtility::fileGetContents($this->confFilePath));

        // Switch to area configuration
        if (!empty($conf)) {
            $this->config->setData($conf);
        } else {
            throw new \RuntimeException('Could not parse "' . $this->confFilePath . '"');
        }
    }

    /**
     * Get context list from current configuration
     *
     * @return array|null
     */
    protected function getContextListFromConfiguration()
    {
        return $this->config->getArray($this->confArea);
    }

    /**
     * Get command list from current configuration
     *
     * @param string $section Section name for commands (startup, final)
     *
     * @return array
     */
    protected function getCommandList($section)
    {
        $ret = array();

        if ($this->contextConfig->exists('command.' . $section)) {
            $ret = $this->contextConfig->get('command.' . $section);
        }

        return $ret;
    }

    /**
     * Build context configuration
     *
     * @param $context
     */
    protected function buildContextConfiguration($context)
    {
        $this->contextConfig = new ConfigReader();

        // Fetch global conf
        $globalConf = array();
        if ($this->config->exists(self::GLOBAL_KEY)) {
            $globalConf = $this->config->get(self::GLOBAL_KEY);
        }

        // Fetch area conf
        $areaConf = $this->config->get($this->confArea);

        // Fetch area global conf
        $areaGlobalConf = array();
        if ($this->config->exists($this->confArea . '.' . self::GLOBAL_KEY)) {
            $areaGlobalConf = $this->config->get($this->confArea . '.' . self::GLOBAL_KEY);
        }

        // Fetch context conf
        if (empty($areaConf[$context])) {
            $this->output->writeln('<p-error>No context "' . $context . '" found</p-error>');
            throw new \CliTools\Exception\StopException(1);
        }
        $contextConf = $areaConf[$context];


        $arrayFilterRecursive = function ($input, $callback) use (&$arrayFilterRecursive) {
            $ret = array();
            foreach ($input as $key => $value) {
                if (is_array($value)) {
                    $value = $arrayFilterRecursive($value, $callback);
                } else {
                    if (strlen($value) == 0) {
                        $value = null;
                    }
                }

                if ($value !== null && $value !== false && $value !== true) {
                    $ret[$key] = $value;
                }
            }

            return $ret;
        };

        // Merge
        $globalConf     = $arrayFilterRecursive($globalConf, 'strlen');
        $areaGlobalConf = $arrayFilterRecursive($areaGlobalConf, 'strlen');
        $contextConf    = $arrayFilterRecursive($contextConf, 'strlen');

        $conf = array_replace_recursive($globalConf, $areaGlobalConf, $contextConf);

        // Set configuration
        $this->contextConfig->setData($conf);
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
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            // Get context selection
            $this->initContext();

            if ($this->input->getOption('config')) {
                // only show configuration
                $this->showContextConfig();
            } else {
                // Create temp directory and check environment
                $this->startup();

                // Run playbook
                $this->runCommands('startup');
                $this->runMain();
                $this->runCommands('finalize');
            }
        } catch (\Exception $e) {
            $this->cleanup();
            throw $e;
        }

        $this->cleanup();
    }

    /**
     * Init context
     */
    protected function initContext()
    {
        $context = $this->getContextFromUser();
        $this->buildContextConfiguration($context);

        // Validate configuration
        if (!$this->validateConfiguration()) {
            $this->output->writeln('<p-error>Configuration could not be validated</p-error>');
            throw new \CliTools\Exception\StopException(1);
        }
    }

    /**
     * Get context from user
     */
    protected function getContextFromUser()
    {
        $ret = null;

        if (!$this->input->getArgument('context')) {
            // ########################
            // Ask user for server context
            // ########################

            $serverList = $this->config->getList($this->confArea);
            $serverList = array_diff($serverList, array(self::GLOBAL_KEY));

            if (empty($serverList)) {
                throw new \RuntimeException('No valid servers found in configuration');
            }

            $serverOptionList = array();

            foreach ($serverList as $context) {
                $line = array();

                // hostname
                $optPath = $this->confArea . '.' . $context . '.ssh.hostname';
                if ($this->config->exists($optPath)) {
                    $line[] = '<info>host:</info>' . $this->config->get($optPath);
                }

                // rsync path
                $optPath = $this->confArea . '.' . $context . '.rsync.path';
                if ($this->config->exists($optPath)) {
                    $line[] = '<info>rsync:</info>' . $this->config->get($optPath);
                }

                // mysql database list
                $optPath = $this->confArea . '.' . $context . '.mysql.database';
                if ($this->config->exists($optPath)) {
                    $dbList        = $this->config->getArray($optPath);
                    $foreignDbList = array();

                    foreach ($dbList as $databaseConf) {
                        if (strpos($databaseConf, ':') !== false) {
                            // local and foreign database in one string
                            $databaseConf    = explode(':', $databaseConf, 2);
                            $foreignDbList[] = $databaseConf[1];
                        } else {
                            // database equal
                            $foreignDbList[] = $databaseConf;
                        }
                    }

                    if (!empty($foreignDbList)) {
                        $line[] .= '<info>mysql:</info>' . implode(', ', $foreignDbList);
                    }
                }

                if (!empty($line)) {
                    $line = implode(' ', $line);
                } else {
                    // fallback
                    $line = $context;
                }

                $serverOptionList[$context] = $line;
            }

            try {
                $question = new ChoiceQuestion('Please choose server context for synchronization', $serverOptionList);
                $question->setMaxAttempts(1);

                $questionDialog = new QuestionHelper();

                $ret = $questionDialog->ask($this->input, $this->output, $question);
            } catch (\InvalidArgumentException $e) {
                // Invalid server context, just stop here
                throw new \CliTools\Exception\StopException(1);
            }
        } else {
            $ret = $this->input->getArgument('context');
        }

        return $ret;
    }

    /**
     * Show context configuration
     */
    protected function showContextConfig()
    {
        print_r($this->contextConfig->get());
    }

    /**
     * Validate configuration (rsync)
     *
     * @return boolean
     */
    protected function validateConfigurationRsync()
    {
        $ret = true;

        // Check if rsync target exists
        if (!$this->getRsyncPathFromConfig()) {
            $this->output->writeln('<p-error>No rsync path configuration found</p-error>');
            $ret = false;
        } else {
            $this->output->writeln('<comment>Using rsync path "' . $this->getRsyncPathFromConfig() . '"</comment>');
        }

        // Check if there are any rsync directories
        if (!$this->contextConfig->exists('rsync.directory')) {
            $this->output->writeln('<comment>No rsync directory configuration found, filesync disabled</comment>');
        }

        return $ret;
    }

    /**
     * Validate configuration (mysql)
     *
     * @return boolean
     */
    protected function validateConfigurationMysql()
    {
        $ret = true;

        // Check if one database is configured
        if (!$this->contextConfig->exists('mysql.database')) {
            $this->output->writeln('<p-error>No mysql database configuration found</p-error>');
            $ret = false;
        }

        return $ret;
    }

    /**
     * Startup task
     */
    protected function startup()
    {
        $this->tempDir = '/tmp/.clisync-' . getmypid();
        $this->clearTempDir();
        PhpUtility::mkdir($this->tempDir, 0777, true);
        PhpUtility::mkdir($this->tempDir . '/mysql/', 0777, true);

        $this->checkIfDockerExists();
    }

    /**
     * Cleanup task
     */
    protected function cleanup()
    {
        $this->clearTempDir();
    }

    /**
     * Clear temp. storage directory if exists
     */
    protected function clearTempDir()
    {
        // Remove storage dir
        if (!empty($this->tempDir) && is_dir($this->tempDir)) {
            $command = new CommandBuilder('rm', '-rf');
            $command->addArgumentSeparator()
                    ->addArgument($this->tempDir);
            $command->executeInteractive();
        }
    }

    /**
     * Check if docker exists
     *
     * @throws \CliTools\Exception\StopException
     */
    protected function checkIfDockerExists()
    {
        $dockerPath = \CliTools\Utility\DockerUtility::searchDockerDirectoryRecursive();

        if (!empty($dockerPath)) {
            $this->output->writeln('<info>Running docker containers:</info>');

            // Docker instance found
            $docker = new CommandBuilder('docker', 'ps');
            $docker->executeInteractive();

            $answer = ConsoleUtility::questionYesNo('Are these running containers the right ones?', 'no');

            if (!$answer) {
                throw new \CliTools\Exception\StopException(1);
            }
        }
    }

    /**
     * Run defined commands
     */
    protected function runCommands($area)
    {
        $commandList = $this->getCommandList($area);

        if (!empty($commandList)) {
            $this->output->writeln('<info> ---- Starting ' . strtoupper($area) . ' commands ---- </info>');

            foreach ($commandList as $commandRow) {

                if (is_string($commandRow)) {
                    // Simple, local task
                    $command = new CommandBuilder();
                    $command->parse($commandRow);
                } elseif (is_array($commandRow)) {
                    // Complex task
                    $command = $this->buildComplexTask($commandRow);
                }

                if ($command) {
                    $command->executeInteractive();
                }
            }
        }
    }

    /**
     * Build complex task
     *
     * @param array $task Task configuration
     *
     * @return CommandBuilder|CommandBuilderInterface
     */
    protected function buildComplexTask(array $task)
    {
        if (empty($task['type'])) {
            $task['type'] = 'local';
        }

        if (empty($task['command'])) {
            throw new \RuntimeException('Task command is empty');
        }

        // Process task type
        switch ($task['type']) {
            case 'remote':
                // Remote command
                $command = new RemoteCommandBuilder();
                $command->parse($task['command']);
                $command = $this->wrapRemoteCommand($command);
                break;

            case 'local':
                // Local command
                $command = new CommandBuilder();
                $command->parse($task['command']);
                break;

            default:
                throw new \RuntimeException('Unknown task type');
                break;
        }

        return $command;
    }

    /**
     * Create rsync command for sync
     *
     * @param string $source  Source directory
     * @param string $target  Target directory
     * @param string $confKey List of files (patterns)
     *
     * @return CommandBuilder
     */
    protected function createRsyncCommandWithConfiguration($source, $target, $confKey)
    {
        $options = array();

        // #############
        // Filelist
        // #############
        $fileList = array();
        if ($this->contextConfig->exists($confKey . '.directory')) {
            $fileList = $this->contextConfig->get($confKey . '.directory');
        }


        // #############
        // Excludes
        // #############
        $excludeList = array();
        if ($this->contextConfig->exists($confKey . '.exclude')) {
            $excludeList = $this->contextConfig->get($confKey . '.exclude');
        }
        // #############
        // Max size
        // #############
        if ($this->contextConfig->exists($confKey . '.conf.maxSize')) {
            $options['max-size'] = array(
                'template' => '--max-size=%s',
                'params'   => array(
                    $this->contextConfig->get($confKey . '.conf.maxSize')
                ),
            );
        }

        // #############
        // Min size
        // #############
        if ($this->contextConfig->exists($confKey . '.conf.minSize')) {
            $options['min-size'] = array(
                'template' => '--min-size=%s',
                'params'   => array(
                    $this->contextConfig->get($confKey . '.conf.minSize')
                ),
            );
        }

        return $this->createRsyncCommand($source, $target, $fileList, $excludeList, $options);
    }

    /**
     * Create rsync command for sync
     *
     * @param string     $source   Source directory
     * @param string     $target   Target directory
     * @param array|null $filelist List of files (patterns)
     * @param array|null $exclude  List of excludes (patterns)
     * @param array|null $options  Custom rsync options
     *
     * @return CommandBuilder
     */
    protected function createRsyncCommand(
        $source,
        $target,
        array $filelist = null,
        array $exclude = null,
        array $options = null
    ) {
        $this->output->writeln('<comment>Rsync from ' . $source . ' to ' . $target . '</comment>');

        $command = new CommandBuilder('rsync', '-rlptD --delete-after --progress --human-readable');

        // Additional options
        if ($this->contextConfig->exists('rsync.opts')) {
            $command->addArgumentRaw($this->contextConfig->get('rsync.opts'));
        }

        // Add file list (external file with --include-from option)
        if (!empty($filelist)) {
            $this->rsyncAddFileList($command, $filelist);
        }

        // Add exclude (external file with --exclude-from option)
        if (!empty($exclude)) {
            $this->rsyncAddExcludeList($command, $exclude);
        }

        if (!empty($options)) {
            foreach ($options as $optionValue) {
                if (is_array($optionValue)) {
                    $command->addArgumentTemplateList($optionValue['template'], $optionValue['params']);
                } else {
                    $command->addArgument($optionValue);
                }
            }
        }

        // Paths should have leading / to prevent sync issues
        $source = rtrim($source, '/') . '/';
        $target = rtrim($target, '/') . '/';

        // Set source and target
        $command->addArgument($source)
                ->addArgument($target);

        return $command;
    }

    /**
     * Get rsync path from configuration
     *
     * @return boolean|string
     */
    protected function getRsyncPathFromConfig()
    {
        $ret = false;
        if ($this->contextConfig->exists('rsync.path')) {
            // Use path from rsync
            $ret = $this->contextConfig->get('rsync.path');
        } elseif ($this->contextConfig->exists('ssh.hostname') && $this->contextConfig->exists('ssh.path')) {
            // Build path from ssh configuration
            $ret = $this->contextConfig->get('ssh.hostname') . ':' . $this->contextConfig->get('ssh.path');
        }

        return $ret;
    }


    /**
     * Get rsync working path (with target if set in config)
     *
     * @return boolean|string
     */
    protected function getRsyncWorkingPath()
    {
        $ret = $this->workingPath;

        // remove right /
        $ret = rtrim($ret, '/');

        if ($this->contextConfig->exists('rsync.workdir')) {
            $ret .= '/' . $this->contextConfig->get('rsync.workdir');
        }

        return $ret;
    }

    /**
     * Add file (pattern) list to rsync command
     *
     * @param CommandBuilder $command Rsync Command
     * @param array          $list    List of files
     */
    protected function rsyncAddFileList(CommandBuilder $command, array $list)
    {
        $rsyncFilter = $this->tempDir . '/.rsync-filelist.' . PhpUtility::uniqueName();

        PhpUtility::filePutContents($rsyncFilter, implode("\n", $list));

        $command->addArgumentTemplate('--files-from=%s', $rsyncFilter);

        // cleanup rsync file
        $command->getExecutor()
                ->addFinisherCallback(
                    function () use ($rsyncFilter) {
                        unlink($rsyncFilter);
                    }
                );
    }

    /**
     * Add exclude (pattern) list to rsync command
     *
     * @param CommandBuilder $command Rsync Command
     * @param array          $list    List of excludes
     */
    protected function rsyncAddExcludeList(CommandBuilder $command, $list)
    {
        $rsyncFilter = $this->tempDir . '/.rsync-exclude.' . PhpUtility::uniqueName();

        PhpUtility::filePutContents($rsyncFilter, implode("\n", $list));
        $command->addArgumentTemplate('--exclude-from=%s', $rsyncFilter);

        // cleanup rsync file
        $command->getExecutor()
                ->addFinisherCallback(
                    function () use ($rsyncFilter) {
                        unlink($rsyncFilter);
                    }
                );
    }

    /**
     * Create mysql backup command
     *
     * @param string $database Database name
     * @param string $dumpFile MySQL dump file
     *
     * @return SelfCommandBuilder
     */
    protected function createMysqlRestoreCommand($database, $dumpFile)
    {
        $command = new SelfCommandBuilder();
        $command->addArgumentTemplate('mysql:restore %s %s', $database, $dumpFile);

        return $command;
    }

    /**
     * Create mysql backup command
     *
     * @param string      $database Database name
     * @param string      $dumpFile MySQL dump file
     * @param null|string $filter   Filter name
     *
     * @return SelfCommandBuilder
     */
    protected function createMysqlBackupCommand($database, $dumpFile, $filter = null)
    {
        $command = new SelfCommandBuilder();
        $command->addArgumentTemplate('mysql:backup %s %s', $database, $dumpFile);

        if ($filter !== null) {
            $command->addArgumentTemplate('--filter=%s', $filter);
        }

        return $command;
    }

    /**
     * Wrap command with ssh if needed
     *
     * @param  CommandBuilderInterface $command
     *
     * @return CommandBuilderInterface
     */
    protected function wrapRemoteCommand(CommandBuilderInterface $command)
    {
        // Wrap in ssh if needed
        if ($this->contextConfig->exists('ssh.hostname')) {
            $sshCommand = new CommandBuilder('ssh', '-o BatchMode=yes');
            $sshCommand->addArgument($this->contextConfig->get('ssh.hostname'))
                       ->append($command, true);

            $command = $sshCommand;
        }

        return $command;
    }

    /**
     * Create new mysql command
     *
     * @param null|string $database Database name
     *
     * @return RemoteCommandBuilder
     */
    protected function createRemoteMySqlCommand($database = null)
    {
        $command = new RemoteCommandBuilder('mysql');
        $command
            // batch mode
            ->addArgument('-B')
            // skip column names
            ->addArgument('-N');

        // Add username
        if ($this->contextConfig->exists('mysql.username')) {
            $command->addArgumentTemplate('-u%s', $this->contextConfig->get('mysql.username'));
        }

        // Add password
        if ($this->contextConfig->exists('mysql.password')) {
            $command->addArgumentTemplate('-p%s', $this->contextConfig->get('mysql.password'));
        }

        // Add hostname
        if ($this->contextConfig->exists('mysql.hostname')) {
            $command->addArgumentTemplate('-h%s', $this->contextConfig->get('mysql.hostname'));
        }

        if ($database !== null) {
            $command->addArgument($database);
        }

        return $command;
    }


    /**
     * Create new mysql command
     *
     * @param null|string $database Database name
     *
     * @return RemoteCommandBuilder
     */
    protected function createLocalMySqlCommand($database = null)
    {
        $command = new RemoteCommandBuilder('mysql');
        $command
            // batch mode
            ->addArgument('-B')
            // skip column names
            ->addArgument('-N');

        // Add username
        if (DatabaseConnection::getDbUsername()) {
            $command->addArgumentTemplate('-u%s', DatabaseConnection::getDbUsername());
        }

        // Add password
        if (DatabaseConnection::getDbPassword()) {
            $command->addArgumentTemplate('-p%s', DatabaseConnection::getDbPassword());
        }

        // Add hostname
        if (DatabaseConnection::getDbHostname()) {
            $command->addArgumentTemplate('-h%s', DatabaseConnection::getDbHostname());
        }

        // Add hostname
        if (DatabaseConnection::getDbPort()) {
            $command->addArgumentTemplate('-P%s', DatabaseConnection::getDbPort());
        }

        if ($database !== null) {
            $command->addArgument($database);
        }

        return $command;
    }

    /**
     * Create new mysqldump command
     *
     * @param null|string $database Database name
     *
     * @return RemoteCommandBuilder
     */
    protected function createRemoteMySqlDumpCommand($database = null)
    {
        $command = new RemoteCommandBuilder('mysqldump');

        // Add username
        if ($this->contextConfig->exists('mysql.username')) {
            $command->addArgumentTemplate('-u%s', $this->contextConfig->get('mysql.username'));
        }

        // Add password
        if ($this->contextConfig->exists('mysql.password')) {
            $command->addArgumentTemplate('-p%s', $this->contextConfig->get('mysql.password'));
        }

        // Add hostname
        if ($this->contextConfig->exists('mysql.hostname')) {
            $command->addArgumentTemplate('-h%s', $this->contextConfig->get('mysql.hostname'));
        }

        // Add custom options
        if ($this->contextConfig->exists('mysql.mysqldump.option')) {
            $command->addArgumentRaw($this->contextConfig->get('mysql.mysqldump.option'));
        }

        // Transfer compression
        switch ($this->contextConfig->get('mysql.compression')) {
            case 'bzip2':
                // Add pipe compressor (bzip2 compressed transfer via ssh)
                $command->addPipeCommand(new CommandBuilder('bzip2', '--compress --stdout'));
                break;

            case 'gzip':
                // Add pipe compressor (gzip compressed transfer via ssh)
                $command->addPipeCommand(new CommandBuilder('gzip', '--stdout'));
                break;
        }

        if ($database !== null) {
            $command->addArgument($database);
        }

        return $command;
    }

    /**
     * Create new mysqldump command
     *
     * @param null|string $database Database name
     *
     * @return RemoteCommandBuilder
     */
    protected function createLocalMySqlDumpCommand($database = null)
    {
        $command = new RemoteCommandBuilder('mysqldump');

        // Add username
        if (DatabaseConnection::getDbUsername()) {
            $command->addArgumentTemplate('-u%s', DatabaseConnection::getDbUsername());
        }

        // Add password
        if (DatabaseConnection::getDbPassword()) {
            $command->addArgumentTemplate('-p%s', DatabaseConnection::getDbPassword());
        }

        // Add hostname
        if (DatabaseConnection::getDbHostname()) {
            $command->addArgumentTemplate('-h%s', DatabaseConnection::getDbHostname());
        }

        // Add hostname
        if (DatabaseConnection::getDbPort()) {
            $command->addArgumentTemplate('-P%s', DatabaseConnection::getDbPort());
        }

        // Add custom options
        if ($this->contextConfig->exists('mysql.mysqldump.option')) {
            $command->addArgumentRaw($this->contextConfig->get('mysql.mysqldump.option'));
        }

        if ($database !== null) {
            $command->addArgument($database);
        }

        // Transfer compression
        switch ($this->contextConfig->get('mysql.compression')) {
            case 'bzip2':
                // Add pipe compressor (bzip2 compressed transfer via ssh)
                $command->addPipeCommand(new CommandBuilder('bzip2', '--compress --stdout'));
                break;

            case 'gzip':
                // Add pipe compressor (gzip compressed transfer via ssh)
                $command->addPipeCommand(new CommandBuilder('gzip', '--stdout'));
                break;
        }

        return $command;
    }


    /**
     * Add mysqldump filter to command
     *
     * @param CommandBuilderInterface $commandDump Command
     * @param string                  $database    Database
     * @param boolean                 $isRemote    Remote filter
     *
     * @return CommandBuilderInterface
     */
    protected function addMysqlDumpFilterArguments(CommandBuilderInterface $commandDump, $database, $isRemote = true)
    {
        $command = $commandDump;

        $filter = $this->contextConfig->get('mysql.filter');

        // get filter
        if (is_array($filter)) {
            $filterList = (array)$filter;
            $filter     = 'custom table filter';
        } else {
            $filterList = $this->getApplication()
                               ->getConfigValue('mysql-backup-filter', $filter);
        }

        if (empty($filterList)) {
            throw new \RuntimeException('MySQL dump filters "' . $filter . '" not available"');
        }

        $this->output->writeln('<p>Using filter "' . $filter . '"</p>');

        // Get table list (from cloned mysqldump command)
        if ($isRemote) {
            $tableListDumper = $this->createRemoteMySqlCommand($database);
        } else {
            $tableListDumper = $this->createLocalMySqlCommand($database);
        }

        $tableListDumper->addArgumentTemplate('-e %s', 'show tables;');

        // wrap with ssh (for remote execution)
        if ($isRemote) {
            $tableListDumper = $this->wrapRemoteCommand($tableListDumper);
        }

        $tableList = $tableListDumper->execute()
                                     ->getOutput();

        // Filter table list
        $ignoredTableList = FilterUtility::mysqlIgnoredTableFilter($tableList, $filterList, $database);

        // Determine size of tables to be dumped and abort if user wishes to
        $size = $this->determineSizeOfTables($database, $ignoredTableList, $isRemote);
        $question = sprintf('The tables in this MySQL dump have total size of %.2f MB! Proceed?', $size);
        if (!ConsoleUtility::questionYesNo($question, 'no')) {
            $this->output->writeln($this->determineBiggestTables($database, $ignoredTableList, $isRemote));
            throw new \CliTools\Exception\StopException(1);
        }

        // Dump only structure
        $commandStructure = clone $command;
        $commandStructure->addArgument('--no-data')
                         ->clearPipes();

        // Dump only data (only filtered tables)
        $commandData = clone $command;
        $commandData->addArgument('--no-create-info')
                    ->clearPipes();

        if (!empty($ignoredTableList)) {
            $commandData->addArgumentTemplateMultiple('--ignore-table=%s', $ignoredTableList);
        }

        $commandPipeList = $command->getPipeList();

        // Combine both commands to one
        $command = new OutputCombineCommandBuilder();
        $command->addCommandForCombinedOutput($commandStructure)
                ->addCommandForCombinedOutput($commandData);

        // Read compression pipe
        if (!empty($commandPipeList)) {
            $command->setPipeList($commandPipeList);
        }

        return $command;
    }

    /**
     * Determine size of tables
     *
     * @param string                  $database         Database
     * @param array                   $ignoredTableList List of ignored tables
     * @param boolean                 $isRemote         Remote filter
     *
     * @return float|null
     */
    protected function determineSizeOfTables($database, $ignoredTableList, $isRemote = true)
    {
        if ($isRemote) {
            $tableSizeCommand = $this->createRemoteMySqlCommand($database);
        } else {
            $tableSizeCommand = $this->createLocalMySqlCommand($database);
        }

        $ignoreTablesSQL = '';
        if (!empty($ignoredTableList)) {
            $ignoreTablesSQL = " AND CONCAT(TABLE_SCHEMA,'.',TABLE_NAME) NOT IN ('".join("','", $ignoredTableList)."')";
        }

        $query  = sprintf("SELECT SUM(ROUND(((data_length + index_length) / 1024 / 1024),2)) 'Size in MB' FROM information_schema.TABLES WHERE table_schema = '%s' AND TABLE_TYPE='BASE TABLE'%s", $database, $ignoreTablesSQL);

        $tableSizeCommand->addArgumentTemplate('-e %s;', $query);
        if ($isRemote) {
            $tableSizeCommand = $this->wrapRemoteCommand($tableSizeCommand);
        }
        $tableSize = $tableSizeCommand->execute()
                                      ->getOutput();

        if ($tableSize && isset($tableSize[0])) {
            return $tableSize[0];
        }

        return null;
    }

    /**
     * Determine which tables are biggest
     *
     * @param string                  $database         Database
     * @param array                   $ignoredTableList List of ignored tables
     * @param boolean                 $isRemote         Remote filter
     *
     * @return string
     */
    protected function determineBiggestTables($database, $ignoredTableList, $isRemote = true)
    {
        if ($isRemote) {
            $tableSizeCommand = $this->createRemoteMySqlCommand($database);
        } else {
            $tableSizeCommand = $this->createLocalMySqlCommand($database);
        }

        $ignoreTablesSQL = '';
        if (!empty($ignoredTableList)) {
            $ignoreTablesSQL = " AND CONCAT(TABLE_SCHEMA,'.',TABLE_NAME) NOT IN ('".join("','", $ignoredTableList)."')";
        }

        $query  = sprintf("SELECT TABLE_NAME, ROUND(((data_length + index_length) / 1024 / 1024),2) 'Size in MB' FROM information_schema.TABLES WHERE table_schema = '%s' AND TABLE_TYPE='BASE TABLE'%s ORDER BY (data_length + index_length) DESC LIMIT 10", $database, $ignoreTablesSQL);

        $tableSizeCommand->addArgumentTemplate('-e %s -t;', $query);
        if ($isRemote) {
            $tableSizeCommand = $this->wrapRemoteCommand($tableSizeCommand);
        }
        $bigTables = $tableSizeCommand->execute()
                                      ->getOutput();

        $output = '';
        if ($bigTables) {
            $output  = '<comment>These are the biggest tables (in MB):</comment>' . "\n";
            $output .= join("\n", $bigTables) . "\n";
            $output .= '<comment>Maybe some of them areņ\'t necessary at all and should be put on the \'filter\' list in your clisync.yml?</comment>';
        }
        return $output;
    }
}
