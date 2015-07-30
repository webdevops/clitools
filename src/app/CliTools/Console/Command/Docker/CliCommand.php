<?php

namespace CliTools\Console\Command\Docker;

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

use CliTools\Shell\CommandBuilder\RemoteCommandBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CliCommand extends AbstractCommand implements \CliTools\Console\Filter\AnyParameterFilterInterface
{

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('docker:cli')
             ->setDescription(
                 'Run cli command in docker container (defined by CLI_SCRIPT and CLI_USER as docker environment variable)'
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
        $ret = 0;

        $paramList = $this->getFullParameterList();
        $container = $this->getApplication()
                          ->getConfigValue('docker', 'container');
        $cliMethod = $this->getApplication()
                          ->getConfigValue('docker', 'climethod');

        switch ($cliMethod) {
            ###########################
            # with Docker exec (faster, complex)
            ###########################
            case 'docker-exec':
                $cliScript = $this->getDockerEnv($container, 'CLI_SCRIPT');
                $cliUser   = $this->getDockerEnv($container, 'CLI_USER');

                if (empty($cliScript)) {
                    $output->writeln(
                        '<p-error>Docker container "' . $container . '" doesn\'t have environment variable "CLI_SCRIPT"</p-error>'
                    );

                    return 1;
                }

                // Create remote cli command
                $command = new RemoteCommandBuilder();
                $command->parse($cliScript)
                        ->addArgumentList($paramList);

                if (!empty($cliUser)) {
                    // sudo wrapping as cli user
                    $commandSudo = new RemoteCommandBuilder('sudo', '-H -E -u %s', array($cliUser));
                    $commandSudo->append($command, false);
                    $command = $commandSudo;
                }

                $this->executeDockerExec($container, $command);
                break;

            ###########################
            # with docker-compose run (simple, slower, requires entrypoint modification)
            ###########################
            case 'dockercompose-run':
                $command = new RemoteCommandBuilder('cli');
                $command->addArgumentList($paramList);

                $ret = $this->executeDockerComposeRun($container, $command);
                break;

            default:
                $output->writeln('<p-error>CliMethod "' . $cliMethod . '" not defined</p-error>');
                $ret = 1;
                break;
        }

        return $ret;
    }
}
