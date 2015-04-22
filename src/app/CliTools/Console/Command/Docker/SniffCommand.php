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

use CliTools\Utility\CommandExecutionUtility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SniffCommand extends AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('docker:sniff')
            ->setDescription('Start network sniffing with docker')
            ->addArgument(
                'protocol',
                InputArgument::REQUIRED,
                'Protocol'
            )
            ->addOption(
                'full',
                null,
                InputOption::VALUE_NONE,
                'Show full output (if supported by protocol)'
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
    public function execute(InputInterface $input, OutputInterface $output) {
        $this->elevateProcess($input, $output);

        $container = 'main';

        $protocol   = $input->getArgument('protocol');
        $fullOutput = $input->getOption('full');

        switch ($protocol) {
            // ##############
            // TCP connections
            // ##############
            case 'con':
            case 'tcp':
                $sniffer = 'tshark';
                $args = '-R "tcp.flags.syn==1 && tcp.flags.ack==0"';
                break;

            // ##############
            // ARP
            // ##############
            case 'arp':
                $sniffer = 'tshark';
                $args = 'arp';
                break;

            // ##############
            // ICMP
            // ##############
            case 'icmp':
                $sniffer = 'tshark';
                $args = 'icmp';
                break;

            // ##############
            // HTTP
            // ##############
            case 'http':
                if ($fullOutput) {
                    $sniffer = 'tshark';
                    $args = 'tcp port 80 or tcp port 443 -2 -V -R "http.request || http.response"';
                } else {
                    $sniffer = 'tshark';
                    $args = 'tcp port 80 or tcp port 443 -2 -V -R "http.request" -Tfields -e ip.dst -e http.request.method -e http.request.full_uri';
                }
                break;

            // ##############
            // SOLR
            // ##############
            case 'solr':
                $sniffer = 'tcpdump';
                $args = '-nl -s0 -w- port 8983 | strings -n8';
                break;

            // ##############
            // ELASTICSEARCH
            // ##############
            case 'elasticsearch':
                $sniffer = 'tcpdump';
                $args = '-A -nn -s 0 \'tcp dst port 9200 and (((ip[2:2] - ((ip[0]&0xf)<<2)) - ((tcp[12]&0xf0)>>2)) != 0)\'';
                break;

            // ##############
            // MEMCACHE
            // ##############
            case 'memcache':
            case 'memcached':
                $sniffer = 'tcpdump';
                $args = '-s 65535 -A -ttt port 11211| cut -c 9- | grep -i \'^get\|set\'';
                break;

            // ##############
            // REDIS
            // ##############
            case 'redis':
                $sniffer = 'tcpdump';
                $args = '-s 65535 tcp port 6379';
                break;

            // ##############
            // SMTP
            // ##############
            case 'smtp':
            case 'mail':
                $sniffer = 'tshark';
                $args = 'tcp -f "port 25" -R "smtp"';
                break;

            // ##############
            // MYSQL
            // ##############
            case 'mysql':
                $sniffer = 'tshark';
                $args = 'tcp -d tcp.port==3306,mysql -T fields -e mysql.query "port 3306"';
                break;

            // ##############
            // DNS
            // ##############
            case 'dns':
                $sniffer = 'tshark';
                $args = '-nn -e ip.src -e dns.qry.name -E separator=" " -T fields port 53';
                break;

            // ##############
            // HELP
            // ##############
            default:
                $output->writeln('<error>Protocol not supported (supported: tcp, icmp, http, solr, elasticsearch, memcache, redis, smtp, mysql, dns)</error>');
                return 1;
                break;
        }

        switch ($sniffer) {
            case 'tshark':
                CommandExecutionUtility::execInteractive('tshark', '-i docker0 ' . $args);
                break;

            case 'tcpdump':
                CommandExecutionUtility::execInteractive('tcpdump', '-i docker0 ' . $args);
                break;

            case 'ngrep':
                CommandExecutionUtility::execInteractive('tcpdump', '-d docker0 ' . $args);
                break;
        }



        return 0;
    }
}
