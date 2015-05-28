#!/usr/bin/env php
<?php

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

error_reporting(E_ALL);
define('CLITOOLS_COMMAND_VERSION', '1.9.908');
define('CLITOOLS_ROOT_FS', __DIR__);

require __DIR__ . '/vendor/autoload.php';

$app = new CliTools\Console\Application('CliTools :: Development Console Utility', CLITOOLS_COMMAND_VERSION);

// App config (from phar)
$app->loadConfig(__DIR__ . '/config.ini');

// Global config
$configFile = '/etc/clitools.ini';
if (is_file($configFile) && is_readable($configFile)) {
    $app->loadConfig($configFile);
}

// User config
$configFile = getenv('HOME') . '/.clitools.ini';
if (is_file($configFile) && is_readable($configFile)) {
    $app->loadConfig($configFile);
}

$app->initialize();
$app->run();
