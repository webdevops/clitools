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
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class SniffCommand extends AbstractCommand
{

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('docker:sniff')
             ->setDescription('Start network sniffing with docker')
             ->addArgument(
                 'protocol',
                 InputArgument::OPTIONAL,
                 'Protocol'
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
        $this->elevateProcess($input, $output);

        $dockerInterface = $this->getApplication()
                                ->getConfigValue('docker', 'interface');

        $output->writeln('<h2>Starting network sniffing</h2>');

        $protocol = $this->getProtocol();

        $command = new CommandBuilder();

        switch ($protocol) {
            // ############################################
            // OSI LEVEL 2
            // ############################################

            // ##############
            // ARP
            // ##############
            case 'arp':
                $output->writeln('<p>Using protocol "arp"</p>');
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
                $output->writeln('<p>Using protocol "icmp"</p>');
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
                $output->writeln('<p>Using protocol "tcp"</p>');
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
                $output->writeln('<p>Using protocol "http"</p>');
                $command->setCommand('tshark');
                $command->addArgumentRaw(
                    'tcp port 80 or tcp port 443 -2 -V -R "http.request" -Tfields -e ip.dst -e http.request.method -e http.request.full_uri'
                );
                break;

            // ##############
            // HTTP (full)
            // ##############
            case 'http-full':
                $output->writeln('<p>Using protocol "http" (full mode)</p>');
                $command->setCommand('tshark');
                $command->addArgumentRaw('tcp port 80 or tcp port 443 -2 -V -R "http.request || http.response"');
                break;

            // ##############
            // PHP-FPM
            // ##############
            case 'php-fpm':
                $output->writeln('<p>Using protocol "php-fpm"</p>');
                $command->setCommand('ngrep');
                $command->addArgumentRaw('port 9000 -W byline');
                break;

            // ##############
            // SOLR
            // ##############
            case 'solr':
                $output->writeln('<p>Using protocol "solr"</p>');
                $command->setCommand('tcpdump');
                $command->addArgumentRaw('-nl -s0 -w- port 8983');

                $pipeCommand = new CommandBuilder('strings', '-n -8');

                $command->addPipeCommand($pipeCommand);
                break;

            // ##############
            // ELASTICSEARCH
            // ##############
            case 'elasticsearch':
                $output->writeln('<p>Using protocol "elasticsearch"</p>');
                $command->setCommand('tcpdump');
                $command->addArgumentRaw(
                    '-A -nn -s 0 \'tcp dst port 9200 and (((ip[2:2] - ((ip[0]&0xf)<<2)) - ((tcp[12]&0xf0)>>2)) != 0)\''
                );
                break;

            // ##############
            // MEMCACHE
            // ##############
            case 'memcache':
            case 'memcached':
                $output->writeln('<p>Using protocol "memcache"</p>');
                $command->setCommand('tcpdump');
                $command->addArgumentRaw('-s 65535 -A -ttt port 11211| cut -c 9- | grep -i \'^get\|set\'');
                break;

            // ##############
            // REDIS
            // ##############
            case 'redis':
                $output->writeln('<p>Using protocol "redis"</p>');
                $command->setCommand('tcpdump');
                $command->addArgumentRaw('-s 65535 tcp port 6379');
                break;

            // ##############
            // SMTP
            // ##############
            case 'smtp':
            case 'mail':
                $output->writeln('<p>Using protocol "smtp"</p>');
                $command->setCommand('tshark');
                $command->addArgumentRaw('tcp -f "port 25" -R "smtp"');
                break;

            // ##############
            // MYSQL
            // ##############
            case 'mysql':
                $output->writeln('<p>Using protocol "mysql"</p>');
                $command->setCommand('tshark');
                $command->addArgumentRaw('tcp -d tcp.port==3306,mysql -T fields -e mysql.query "port 3306"');
                break;

            // ##############
            // DNS
            // ##############
            case 'dns':
                $output->writeln('<p>Using protocol "dns"</p>');
                $command->setCommand('tshark');
                $command->addArgumentRaw('-nn -e ip.src -e dns.qry.name -E separator=" " -T fields port 53');
                break;

            // ##############
            // HELP
            // ##############
            default:
                $output->writeln('<p-error>Protocol not supported:</p-error>');
                $output->writeln(
                    '<p-error>  OSI layer 7: http, solr, elasticsearch, memcache, redis, smtp, mysql, dns</p-error>'
                );
                $output->writeln('<p-error>  OSI layer 4: tcp</p-error>');
                $output->writeln('<p-error>  OSI layer 3: icmp</p-error>');
                $output->writeln('<p-error>  OSI layer 2: arp</p-error>');

                return 1;
                break;
        }

        switch ($command->getCommand()) {
            case 'tshark':
                $output->writeln('<p>Using sniffer "tshark"</p>');
                $command->addArgumentTemplate('-i %s', $dockerInterface);
                break;

            case 'tcpdump':
                $output->writeln('<p>Using sniffer "tcpdump"</p>');
                $command->addArgumentTemplate('-i %s', $dockerInterface);
                break;

            case 'ngrep':
                $output->writeln('<p>Using sniffer "ngrep"</p>');
                $command->addArgumentTemplate('-d %s', $dockerInterface);
                break;
        }

        $this->setTerminalTitle('sniffer', $protocol, '(' . $command->getCommand() . ')');

        $command->executeInteractive();

        return 0;
    }


    /**
     * Get protocol
     *
     * @return string
     */
    protected function getProtocol()
    {
        $ret = null;

        if (!$this->input->getArgument('protocol')) {
            $protocolList = array(
                'http'          => 'HTTP (requests only)',
                'http-full'     => 'HTTP (full)',
                'php-fpm'       => 'PHP-FPM',
                'solr'          => 'Solr',
                'elasticsearch' => 'Elasticsearch',
                'memcache'      => 'Memcache',
                'redis'         => 'Redis',
                'smtp'          => 'SMTP',
                'mysql'         => 'MySQL queries',
                'dns'           => 'DNS',
                'tcp'           => 'TCP',
                'icmp'          => 'ICMP',
                'arp'           => 'ARP',
            );

            try {
                $question = new ChoiceQuestion('Please choose network protocol for sniffing', $protocolList);
                $question->setMaxAttempts(1);

                $questionDialog = new QuestionHelper();

                $ret = $questionDialog->ask($this->input, $this->output, $question);
            } catch (\InvalidArgumentException $e) {
                // Invalid server context, just stop here
                throw new \CliTools\Exception\StopException(1);
            }
        } else {
            $ret = $this->input->getArgument('protocol');
        }


        return $ret;
    }
}
