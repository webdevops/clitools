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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class CleanupCommand extends AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('docker:cleanup')
            ->setDescription('Cleanup docker environment')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force deletion'
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
            $command = new CommandBuilder('docker', 'images -qf "dangling=true"');
            $imageList = $command->execute()->getOutput();

            if (!empty($imageList)) {
                $this->output->writeln('<p>Found ' . number_format(count($imageList)) . ' images for cleanup</p>');

                while (!empty($imageList)) {
                    $command = new CommandBuilder('docker', 'rmi');
                    if ($this->input->getOption('force')) {
                        $command->addArgument('--force');
                    }
                    $command->addArgumentList(array_splice($imageList, 0, 50));
                    $command->executeInteractive();
                }
            } else {
                $this->output->writeln('<p>No images for cleanup found</p>');
            }

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

         try {
             $command = new CommandBuilder('docker', 'volume ls -qf "dangling=true"');
             $volumeList = $command->execute()->getOutput();

             if (!empty($volumeList)) {
                 $this->output->writeln('<p>Found ' . number_format(count($volumeList)) . ' volumes for cleanup</p>');

                 while (!empty($volumeList)) {
                     $command = new CommandBuilder('docker', 'volume rm');
                     if ($this->input->getOption('force')) {
                         $command->addArgument('--force');
                     }
                     $command->addArgumentList(array_splice($volumeList, 0, 50));
                     $command->executeInteractive();
                 }
             } else {
                 $this->output->writeln('<p>No volumes for cleanup found</p>');
             }
         } catch (\Exception $e) {
             $this->output->writeln('<comment>Some volumes could not be removed (this is normal)</comment>');
         }

        $this->output->writeln('');
    }

}
