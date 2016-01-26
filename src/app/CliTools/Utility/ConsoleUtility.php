<?php

namespace CliTools\Utility;

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

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

abstract class ConsoleUtility
{

    /**
     * Input
     *
     * @var InputInterface
     */
    static protected $input = null;

    /**
     * Input
     *
     * @var OutputInterface
     */
    static protected $output = null;

    /**
     * Initializes the command just after the input has been validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    public static function initialize(InputInterface $input, OutputInterface $output)
    {
        self::$input  = $input;
        self::$output = $output;
    }

    /**
     * Get input
     *
     * @return InputInterface
     */
    public static function input()
    {
        return self::$input;
    }

    /**
     * Get output
     *
     * @return OutputInterface
     */
    public static function getOutput()
    {
        return self::$output;
    }

    /**
     * Verbose write line
     *
     * @param string $area Area
     * @param string $line Line
     */
    public static function verboseWriteln($area, $line)
    {
        if (self::$output->isVerbose()) {

            // Special stuff if line is exception
            if ($line instanceof \Exception) {
                /** @var \Exception $e */
                $e      = $line;
                $line   = array();
                $line[] = '--- EXCEPTION ---';
                $line[] = $e->getMessage();
                $line[] = ' FILE: ' . $e->getFile();
                $line[] = ' LINE: ' . $e->getLine();
                $line   = implode("\n", $line);
            }

            // Process lines
            $lineList = explode("\n", $line);
            if (count($lineList) >= 1) {
                unset($line);
                foreach ($lineList as &$line) {
                    $line = '   ' . ltrim($line);
                }
                unset($line);
                $line = implode("\n", $lineList);
            }

            $line = sprintf("<info>[VERBOSE %s]</info>\n<comment>%s</comment>\n", $area, $line);

            self::$output->writeln($line);
        }
    }

    /**
     * Ask question with yes/no detection
     *
     * @param string $question Question
     * @param string $default  Default
     *
     * @return bool
     */
    public static function questionYesNo($message, $default)
    {
        $ret = false;

        while (1) {
            $question       = new Question('<question> >>> ' . $message . '</question> [yes/no] ', $default);
            $questionDialog = new QuestionHelper();
            $answer         = $questionDialog->ask(self::$input, self::$output, $question);

            if (stripos($answer, 'n') === 0) {
                $ret = false;
                break;
            } elseif (stripos($answer, 'y') === 0) {
                $ret = true;
                break;
            }
        }

        return $ret;
    }
}
