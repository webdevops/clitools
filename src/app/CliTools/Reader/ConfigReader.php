<?php

namespace CliTools\Reader;

use CliTools\Console\Shell\CommandBuilder\CommandBuilder;

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

class ConfigReader implements \ArrayAccess {

    /**
     * Data storage
     *
     * @var array
     */
    protected $data = array();

    /**
     * Constructor
     *
     * @param array $data Data configuration
     */
    public function __construct(array $data = null) {
        if ($data !== null) {
            $this->setData($data);
        }
    }

    /**
     * Set configuration data
     *
     * @param array $data Data configuration
     */
    public function setData(array $data) {
        $this->data = $data;
    }

    /**
     * Get value from specific node (dotted array notation)
     *
     * @param string $path Path to node (eg. foo.bar.baz)
     * @return mixed|null
     */
    public function get($path) {
        return $this->getNode($path);
    }

    /**
     * Get array value from specific node (dotted array notation)
     *
     * @param string $path Path to node (eg. foo.bar.baz)
     * @return array|null
     */
    public function getArray($path) {
        $ret = $this->getNode($path);

        if (!is_array($ret)) {
            $ret = array();
        }

        return $ret;
    }

    /**
     * Set value to specific node (dotted array notation)
     *
     * @param string $path  Path to node (eg. foo.bar.baz)
     * @param mixed  $value Value to set
     */
    public function set($path, $value) {
        $node =& $this->getNode($path);
        $node = $value;
    }

    /**
     * Clear value at specific node (dotted array notation)
     *
     * @param string $path  Path to node (eg. foo.bar.baz)
     */
    public function clear($path) {
        $node =& $this->getNode($path);
        $node = null;
    }

    /**
     * Check if specific node exists
     *
     * @param string $path Path to node (eg. foo.bar.baz)
     * @return bool
     */
    public function exists($path) {
        return ($this->getNode($path) !== null);
    }

    /**
     * Get node by reference
     *
     * @param string $path Path to node (eg. foo.bar.baz)
     * @return mixed|null
     */
    protected function &getNode($path) {
        $pathList = explode('.',$path);
        $data = &$this->data;

        foreach ($pathList as $node) {
            if (isset($data[$node])) {
                $data = &$data[$node];
            } else {
                unset($data);
                $data = null;
                break;
            }
        }

        if ($data !== null) {
            $ret = &$data;
        }

        return $ret;
    }

    /**
     * Array accessor: Set value to offset
     *
     * @param string $offset Array key
     * @param mixed  $value  Value
     */
    public function offsetSet($offset, $value) {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Array accessor: Check if offset exists
     *
     * @param string $offset Array key
     * @return boolean
     */
    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    /**
     * Array accessor: Unset offset
     *
     * @param string $offset Array key
     */
    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    /**
     * Array accessor: Get value at offset
     *
     * @param string $offset Array key
     * @return mixed
     */
    public function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

}
