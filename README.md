# CliTools for Vagrant VM, Debian and Ubuntu

![latest v1.8.0](https://img.shields.io/badge/latest-v1.8.0-green.svg?style=flat)
![License GPL3](https://img.shields.io/badge/license-GPL3-blue.svg?style=flat)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/9f12f125-3623-4b9d-b01b-07090f91e416/big.png)](https://insight.sensiolabs.com/projects/9f12f125-3623-4b9d-b01b-07090f91e416)


Documentation is still WIP :)

## Requirements

- PHP 5.5
- Tools
  - git
  - wget
  - multitail
  - tshark
  - tcpdump
  - ngrep
  - strace
  - lsof
  - sudo
  - moreutils (ifdata)
  - coreutils (grep, sort, uniq, awk, cat, df, ip, cut, lsb_release, wall)
  - docker and docker-compose (if you want to use docker)
  - mysql (if you want to use mysql)

## Installation


```bash
# Download latest tools (or in ~/bin if you have it in $PATH)
wget -O/usr/local/bin/ct https://www.achenar.net/clicommand/clitools.phar

# Set executable bit
chmod 777 /usr/local/bin/ct

# Download example config
wget -O"$HOME/.clitools.ini" https://raw.githubusercontent.com/mblaschke/vagrant-development/develop/provision/ansible/roles/clitools/files/clitools.ini
```

Now you can use following aliases (some aliases requires clitools 1.8.0!):

```bash
# Shortcut for docker-compose (autosearch docker-compose.yml in up-dir, you don't have to be in directory with docker-compose.yml)
alias dcc='ct docker:compose'

# Enter main docker container
alias dcshell='ct docker:shell'
alias dcsh='ct docker:shell'

# Execute predefined cli in docker container
alias dcli='ct docker:cli'

# Execute mysql client in docker container
alias dcsql='ct docker:mysql'
alias dcmysql='ct docker:mysql'
```

## Configuration

CliTools will read /etc/clitools.ini for system wide configuration.

Defaults available in [config.ini](https://github.com/mblaschke/vagrant-clitools/blob/master/src/config.ini)

### Docker specific configuration
```ini
[config]
; ssh_conf_path   = "/vagrant/provision/sshconfig/"

[db]
dsn = "mysql:host=127.0.0.1;port=13306"
username = "root"
password = "dev"
debug_log_dir = "/tmp/debug/"

[syscheck]
enabled = 1
wall = 1
growl = 1
diskusage = 85

[growl]
server = 192.168.56.1
password =

[commands]
; not used commands here
ignore[] = "CliTools\Console\Command\Log\ApacheCommand"
ignore[] = "CliTools\Console\Command\Log\PhpCommand"
ignore[] = "CliTools\Console\Command\Log\DebugCommand"
ignore[] = "CliTools\Console\Command\Apache\RestartCommand"
ignore[] = "CliTools\Console\Command\Mysql\RestartCommand"
ignore[] = "CliTools\Console\Command\Php\RestartCommand"
ignore[] = "CliTools\Console\Command\System\UpdateCommand"
ignore[] = "CliTools\Console\Command\System\RebootCommand"
```

## Commands

### Special commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct self-update             | Update ct command (download new version)                                  |
| ct update                  | Updates all system components, ssh configuration, ct command update etc.  |

### System commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct shutdown (alias)        | Shutdown system                                                           |

### Log commands

All log commands are using a grep-filter (specified as optional argument)

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct log:mail                | Shows mail logs                                                           |

### Docker commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct docker:shell            | Jump into a shell inside a docker container                               |
|                            | __ct docker:shell__ -> enter main container                               |
|                            | __ct docker:shell mysql__ -> enter mysql container                        |
| ct docker:mysql            | Jump into a mysql client inside a docker container                        |
|                            | __ct docker:mysql__ -> execute mysql client inside main container         |
| ct docker:sniff            | Start network sniffer for various protocols                               |
|                            | __ct docker:sniff http__ -> start HTTP sniffing                           |
| ct docker:exec             | Execute command in docker container                                       |
|                            | __ct docker:exec ps__ -> run 'ps' inside main container                   |
| ct docker:cli              | Execute special cli command in docker container                           |
|                            | __ct docker:cli scheduler__ -> run 'scheduler' in TYPO3 CMS               |
| ct docker:compose          | Execute docker-compose (recursive up-searching for docker-compose.yml)    |
|                            | __ct docker:compose ps__ -> list all running docker-compose containers    |

### MySQL commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct mysql:clear             | Clear database (remove all tables in database)                            |
|                            | __ct mysql:clear typo3__                                                  |
| ct mysql:connections       | Lists all current connections                                             |
| ct mysql:create            | Create (and drops if already exists) a database                           |
|                            | __ct mysql:create typo3__                                                 |
| ct mysql:debug             | Shows mysql debug log (lists all queries) with basic filter support       |
|                            | __ct mysql:debug__  (full log)                                            |
|                            | __ct mysql:debug tt_content__  (full log)                                 |
| ct mysql:slowlog           | Shows mysql slow log                                                      |
|                            | __ct mysql:slowlog__  (show slow queries with 1 sec and more)             |
|                            | __ct mysql:slowlog --time=10__  (show slow queries with 10 sec and more)  |
|                            | __ct mysql:slowlog --no-index__ (show not using index and slow (1sec) queries) |
| ct mysql:drop              | Drops a database                                                          |
|                            | __ct mysql:drop typo3__                                                   |
| ct mysql:list              | Lists all databases with some statitics                                   |
| ct mysql:restart           | Restart MySQL server                                                      |
| ct mysql:restore           | Create (and drops if already exists) a database and restore from a dump   |
|                            | Dump file can be plaintext, gziped, bzip2 or lzma compressed              |
|                            | and will automatically detected                                           |
|                            | __ct mysql:restore typo3 dump.sql.bz2__                                   |

### PHP commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct php:trace               | Trace syscalls from one or all PHP processes (strace)                     |
|                            | __ct php:trace --all__ -> Trace all php processes immediately             |

### Samba commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct samba:restart           | Restart Samba server                                                      |


### System commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct system:env              | Lists common environment variables                                        |
| ct system:openfiles        | Lists current open files count grouped by process                         |
| ct system:shutdown         | Shutdown system                                                           |
| ct system:swap             | Show swap usage for running processes                                     |
| ct system:update           | Updates all system components, ssh configuration, ct command update etc.  |
| ct system:version          | Shows version for common packages                                         |

### TYPO3 commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct typo3:beuser            | Injects a dev user (pass dev) to all or one specified TYPO3 database      |
|                            | __ct typo3:beuser__                                                       |
|                            | __ct typo3:beuser typo3__                                                 |
| ct typo3:cleanup           | Cleanup command tables to same some table space                           |
|                            | __ct typo3:cleanup__                                                      |
|                            | __ct typo3:cleanup typo3__                                                |

### User commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct user:rebuildsshconfig   | Rebuild SSH config from ct repository (/vagrant/provision/sshconfig)      |

