<?php

namespace CliTools\Service;

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

class SettingsService {

    /**
     * Path to settings file
     *
     * @var string
     */
    protected $settingsPath;

    /**
     * Values
     *
     * @var array
     */
    protected $values = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->settingsPath = getenv('HOME') . '/.clitools.state';
        $this->load();
    }

    /**
     * Set value
     *
     * @param string $key    Setting key
     * @param mixed  $value  Value
     */
    public function set($key, $value) {
        $this->values[$key] = $value;
    }

    /**
     * Get value from settings storage
     *
     * @param string $key Setting key
     *
     * @return null|mixed
     */
    public function get($key) {
        $ret = null;

        if (array_key_exists($key, $this->values)) {
            $ret = $this->values[$key];
        }

        return $ret;
    }

    /**
     * Load settings from .clitool.state file
     */
    public function load() {
        if (file_exists($this->settingsPath)) {
            $content = file_get_contents($this->settingsPath);
            $this->values = json_decode($content, true);
        }
    }

    /**
     * Store settings to .clitool.state file
     */
    public function store() {
        file_put_contents($this->settingsPath, json_encode($this->values));
    }
}
