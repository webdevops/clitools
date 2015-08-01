<?php

namespace CliTools\Console\Command\Common;

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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixRightsCommand extends \CliTools\Console\Command\AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('fix-rights')
             ->setDescription('Fix rights of multiple directories and files')
             ->addArgument(
                 'path',
                 InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                 'Path (multiple)'
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
        $pathList = $this->input->getArgument('path');

        $this->checkPathList($pathList);


        foreach ($pathList as $path) {
            if (is_dir($path)) {
                $iterator = new \RecursiveDirectoryIterator($path);
                $iterator = new \RecursiveIteratorIterator($iterator);

                /** @var \SplFileInfo $entry */
                foreach ($iterator as $entry) {
                    $this->setRights($entry);
                }
            } else {
                $entry = new \SplFileInfo($path);
                $this->setRights($entry);
            }
        }
    }

    /**
     * Set rights for file
     *
     * @param \SplFileInfo $file
     */
    protected function setRights(\SplFileInfo $file)
    {
        $isDir = false;

        if ($file->isDir()) {
            $perms = fileperms($file->getPathname());
            $isDir = true;
        } elseif ($file->isFile()) {
            $perms = fileperms($file->getPathname());
        }

        // Owner
        $perms = $perms | 0x0100;
        $perms = $perms | 0x0080;
        if ($isDir) {
            $perms = $perms | 0x0800;
        }

        // Group
        $perms = $perms | 0x0020;
        $perms = $perms | 0x0010;
        if ($isDir) {
            $perms = $perms | 0x0800;
        }

        // Others
        $perms = $perms | 0x0004;
        $perms = $perms | 0x0002;
        if ($isDir) {
            $perms = $perms | 0x0800;
        }

        chmod($file->getPathname(), $perms);
    }

    /**
     * Check path list
     *
     * @param $pathList
     *
     * @throws \RuntimeException
     */
    protected function checkPathList($pathList)
    {
        foreach ($pathList as $path) {
            if (!file_exists($path)) {
                throw new \RuntimeException('Path "' . $path . '" does not exists or is not writeable');
            }
        }
    }
}
