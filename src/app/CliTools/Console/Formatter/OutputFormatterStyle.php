<?php

namespace CliTools\Console\Formatter;

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

class OutputFormatterStyle extends \Symfony\Component\Console\Formatter\OutputFormatterStyle {

    protected static $availableForegroundColors = array(
        'black' => array('set' => 30, 'unset' => 39),
        'red' => array('set' => 31, 'unset' => 39),
        'green' => array('set' => 32, 'unset' => 39),
        'yellow' => array('set' => 33, 'unset' => 39),
        'blue' => array('set' => 34, 'unset' => 39),
        'magenta' => array('set' => 35, 'unset' => 39),
        'cyan' => array('set' => 36, 'unset' => 39),
        'white' => array('set' => 37, 'unset' => 39),
    );
    protected static $availableBackgroundColors = array(
        'black' => array('set' => 40, 'unset' => 49),
        'red' => array('set' => 41, 'unset' => 49),
        'green' => array('set' => 42, 'unset' => 49),
        'yellow' => array('set' => 43, 'unset' => 49),
        'blue' => array('set' => 44, 'unset' => 49),
        'magenta' => array('set' => 45, 'unset' => 49),
        'cyan' => array('set' => 46, 'unset' => 49),
        'white' => array('set' => 47, 'unset' => 49),
    );
    protected static $availableOptions = array(
        'bold' => array('set' => 1, 'unset' => 22),
        'underscore' => array('set' => 4, 'unset' => 24),
        'blink' => array('set' => 5, 'unset' => 25),
        'reverse' => array('set' => 7, 'unset' => 27),
        'conceal' => array('set' => 8, 'unset' => 28),
    );

    /**
     * Padding
     *
     * @var null|integer
     */
    protected $padding;

    /**
     * Padding
     *
     * @var null|integer
     */
    protected $paddingOutside;

    /**
     * Wrap
     *
     * @var null|string
     */
    protected $wrap;

    /**
     * Application
     *
     * @var \CliTools\Console\Application
     */
    protected $application;

    /**
     * Set padding
     *
     * @param integer|string $padding Padding
     */
    public function setPadding($padding) {
        $this->padding = $padding;
    }

    /**
     * Set padding
     *
     * @param integer|string $padding Padding
     */
    public function setPaddingOutside($padding) {
        $this->paddingOutside = $padding;
    }

    /**
     * Set application
     *
     * @param \CliTools\Console\Application $app Application
     */
    public function setApplication(\CliTools\Console\Application $app) {
        $this->application = $app;
    }

    /**
     * Set wrap
     *
     * @param string $wrap Wrap value
     */
    public function setWrap($wrap) {
        $this->wrap = $wrap;
    }

    /**
     * Applies the style to a given text.
     *
     * @param string $text The text to style
     *
     * @return string
     */
    public function apply($text) {

        $ret = $text;

        // ##################
        // Padding
        // ##################

        if (!empty($this->padding)) {
            $ret = $this->padding . $ret;
        }


        // ##################
        // Wrap
        // ##################

        if (!empty($this->wrap)) {
            list($width) = $this->application->getTerminalDimensions();

            $length = strlen($text);
            $wrapLength = (int)($width - $length - 2)/2 * 0.5;

            if ($wrapLength >= 1) {
                $ret = str_repeat($this->wrap, $wrapLength) . ' '. $ret . ' ' . str_repeat($this->wrap, $wrapLength);
            }
        }

        $ret = parent::apply($ret);

        // ##################
        // Padding
        // ##################

        if (!empty($this->paddingOutside)) {
            $ret = $this->paddingOutside . $ret;
        }

        return $ret;
    }
}
