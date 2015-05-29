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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CliTools\Console\Shell\CommandBuilder\CommandBuilder;

class SniffCommand extends AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this
            ->setName('docker:sniff')
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
            )
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_NONE,
                'Additonal filter'
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

        $dockerInterface = $this->getApplication()->getConfigValue('docker', 'interface');

        $protocol   = $input->getArgument('protocol');
        $fullOutput = $input->getOption('full');

        $command = new CommandBuilder();

        switch ($protocol) {
            // ############################################
            // OSI LEVEL 2
            // ############################################

            // ##############
            // ARP
            // ##############
            case 'arp':
                $command->setCommand('tshark');
                $command->addArgument('arp');
                break;

            // ############################################
            // OSI LEVEL 3
            // ############################################

            // ##############
            // ICMP
            // ##############
            case 'icmp':
                $command->setCommand('tshark');
                $command->addArgument('icmp');
                break;

            // ############################################
            // OSI LEVEL 4
            // ############################################

            // ##############
            // TCP connections
            // ##############
            case 'con':
            case 'tcp':
                $command->setCommand('tshark');
                $command->addArgumentRaw('-R "tcp.flags.syn==1 && tcp.flags.ack==0"');
                break;

            // ############################################
            // OSI LEVEL 5-7
            // ############################################

            // ##############
            // HTTP
            // ##############
            case 'http':
                $command->setCommand('tshark');

                if ($fullOutput) {
                    $command->addArgumentRaw('tcp port 80 or tcp port 443 -2 -V -R "http.request || http.response"');
                } else {
                    $command->addArgumentRaw('tcp port 80 or tcp port 443 -2 -V -R "http.request" -Tfields -e ip.dst -e http.request.method -e http.request.full_uri');
                }
                break;

            // ##############
            // SOLR
            // ##############
            case 'solr':
                $command->setCommand('tcpdump');
                $command->addArgumentRaw('-nl -s0 -w- port 8983');

                $pipeCommand = new CommandBuilder('strings', '-n -8');

                $command->addPipeCommand($pipeCommand);
                break;

            // ##############
            // ELASTICSEARCH
            // ##############
            case 'elasticsearch':
                $command->setCommand('tcpdump');
                $command->addArgumentRaw('-A -nn -s 0 \'tcp dst port 9200 and (((ip[2:2] - ((ip[0]&0xf)<<2)) - ((tcp[12]&0xf0)>>2)) != 0)\'');
                break;

            // ##############
            // MEMCACHE
            // ##############
            case 'memcache':
            case 'memcached':
                $command->setCommand('tcpdump');
                $command->addArgumentRaw('-s 65535 -A -ttt port 11211| cut -c 9- | grep -i \'^get\|set\'');
                break;

            // ##############
            // REDIS
            // ##############
            case 'redis':
                $command->setCommand('tcpdump');
                $command->addArgumentRaw('-s 65535 tcp port 6379');
                break;

            // ##############
            // SMTP
            // ##############
            case 'smtp':
            case 'mail':
                $command->setCommand('tshark');
                $command->addArgumentRaw('tcp -f "port 25" -R "smtp"');
                break;

            // ##############
            // MYSQL
            // ##############
            case 'mysql':
                $command->setCommand('tshark');
                $command->addArgumentRaw('tcp -d tcp.port==3306,mysql -T fields -e mysql.query "port 3306"');
                break;

            // ##############
            // DNS
            // ##############
            case 'dns':
                $command->setCommand('tshark');
                $command->addArgumentRaw('-nn -e ip.src -e dns.qry.name -E separator=" " -T fields port 53');
                break;

            // ##############
            // HELP
            // ##############
            default:
                $output->writeln('<error>Protocol not supported:</error>');
                $output->writeln('<comment>  OSI layer 7: http, solr, elasticsearch, memcache, redis, smtp, mysql, dns</comment>');
                $output->writeln('<comment>  OSI layer 4: tcp</comment>');
                $output->writeln('<comment>  OSI layer 3: icmp</comment>');
                $output->writeln('<comment>  OSI layer 2: arp</comment>');
                return 1;
                break;
        }

        switch ($command->getCommand()) {
            case 'tshark':
                $command->addArgumentTemplate('-i %s', $dockerInterface);


                break;

            case 'tcpdump':
                $command->addArgumentTemplate('-i %s', $dockerInterface);
                break;

            case 'ngrep':
                $command->addArgumentTemplate('-d %s', $dockerInterface);
                break;
        }

        $command->executeInteractive();

        return 0;
    }
}
