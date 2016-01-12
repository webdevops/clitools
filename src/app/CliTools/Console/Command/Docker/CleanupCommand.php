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

use CliTools\Shell\CommandBuilder\CommandBuilder;
use CliTools\Shell\CommandBuilder\RemoteCommandBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupCommand extends AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('docker:cleanup')
            ->setDescription('Cleanup docker environment');
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
        $this->cleanDockerImages();
        $this->cleanDockerVolumes();
        $output->writeln('<comment>Cleanup finished</comment>');
    }

    /**
     * Clean docker images
     */
    protected function cleanDockerImages()
    {
        $this->output->writeln('<h2>Cleanup orphaned docker images</h2>');
        try {
            $command = new CommandBuilder('docker', 'images');
            $command
                ->addPipeCommand( new CommandBuilder('grep', '"<none>"') )
                ->addPipeCommand( new CommandBuilder('awk', '"{print \$3}"') )
                ->addPipeCommand( new CommandBuilder('xargs', '--no-run-if-empty docker rmi -f') );
            $command->executeInteractive();

        } catch (\Exception $e) {
            $this->output->writeln('<comment>Some images could not be removed (this is normal)</comment>');
        }

        $this->output->writeln('');
    }

    /**
     * Clean docker volumes
     */
    protected function cleanDockerVolumes()
    {
        $this->output->writeln('<h2>Cleanup orphaned docker volumes</h2>');
        $this->output->writeln('<info>Updating docker image "martin/docker-cleanup-volumes"</info>');

        $command = new CommandBuilder('docker', 'pull martin/docker-cleanup-volumes');
        $command->executeInteractive();

        $this->output->writeln('<info>Run docker image "martin/docker-cleanup-volumes"</info>');
        $command = new CommandBuilder('docker', 'run -v /var/run/docker.sock:/var/run/docker.sock -v /var/lib/docker:/var/lib/docker --rm martin/docker-cleanup-volumes');
        $command->executeInteractive();

        $this->output->writeln('');
    }

}
