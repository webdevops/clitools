# CliTools for Docker, PHP und MySQL development

![latest v1.9.0](https://img.shields.io/badge/latest-v1.9.0-green.svg?style=flat)
![License GPL3](https://img.shields.io/badge/license-GPL3-blue.svg?style=flat)
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/mblaschke/clitools.svg)](http://isitmaintained.com/project/mblaschke/clitools "Average time to resolve an issue")
[![Percentage of issues still open](http://isitmaintained.com/badge/open/mblaschke/clitools.svg)](http://isitmaintained.com/project/mblaschke/clitools "Percentage of issues still open")

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/9f12f125-3623-4b9d-b01b-07090f91e416/big.png)](https://insight.sensiolabs.com/projects/9f12f125-3623-4b9d-b01b-07090f91e416)


CliTools is a terminal utility for some handy convierence tasks based on Symfony Components (Console).

Documentation is still WIP :)

## Requirements

- PHP 5.5 (CLI)
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
# Shortcut for auto-tree-searching make
alias make='ct make'

# Shortcut for auto-tree-searching make
alias composer='ct php:composer'

# Shortcut for docker-compose (autosearch docker-compose.yml in up-dir, you don't have to be in directory with docker-compose.yml)
alias dcc='ct docker:compose'

# Startup docker-container (and shutdown previous one, v1.9.0 and up)
alias dccup='ct docker:up'
alias dccstop='ct docker:compose stop'

# Enter main docker container (as CLI_USER if available - if not specified then root is used)
alias dcshell='ct docker:shell'
alias dcsh='ct docker:shell'

# Enter main docker container (as root)
alias dcroot='ct docker:root'

# Execute predefined cli in docker container
alias dccrun='ct docker:cli'
alias dcrun='ct docker:cli'

# Execute mysql client in docker container
alias dcsql='ct docker:mysql'
alias dcmysql='ct docker:mysql'
```

## Configuration

CliTools will read /etc/clitools.ini (system wide) and ~/.clitools.ini (personal) for configuration

The [default configuration](https://github.com/mblaschke/vagrant-clitools/blob/develop/src/config.ini) is inside the phar.

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
| ct make                    | Search for "Makefile" in tree and start "make" in this directory          |

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
| ct docker:create           | Create new docker boilerplate in directory (first argument)               |
|                            | __ct docker:create projectname__ -> Create new docker boilerplate instance in directory "projectname" |
|                            | __ct docker:create projectname --code=git@github.com/foo/bar__ -> Create new docker boilerplate instance in directory "projectname" and git code repository |
|                            | __ct docker:create projectname --docker=git@github.com/foo/bar__ -> Create new docker boilerplate instance in directory "projectname" and custom docker boilerplate repository |
|                            | __ct docker:create projectname --code=git@github.com/foo/bar --make=build__ -> Create new docker boilerplate instance in directory "projectname" and git code repository, will run automatic make (Makefile) task "build" after checkout |
| ct docker:shell            | Jump into a shell inside a docker container (using predefined user defined with CLI_USER in docker env) |
|                            | __ct docker:shell__ -> enter main container                               |
|                            | __ct docker:shell mysql__ -> enter mysql container                        |
|                            | __ct docker:shell --user=www-data -> enter main container as user www-data |
| ct docker:root             | Jump into a shell inside a docker container as root user                  |
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
| ct mysql:backup            | Backup a database to file                                                 |
|                            | Compression type will be detected from file extension (default plain sql) |
|                            | __ct mysql:restore typo3 dump.sql__ -> plain sql dump                     |
|                            | __ct mysql:restore typo3 dump.sql.gz__ -> gzip'ed sql dump                |
|                            | __ct mysql:restore typo3 dump.sql.bzip2__ -> bzip2'ed sql dump            |
|                            | __ct mysql:restore typo3 dump.sql.xz__ -> xz'ed (lzma'ed) sql dump        |
|                            | __ct mysql:restore typo3 dump.sql --filter=typo3__ -> No TYPO3 cache tables in dump |
| ct mysql:restore           | Create (and drops if already exists) a database and restore from a dump   |
|                            | Dump file can be plaintext, gziped, bzip2 or lzma compressed              |
|                            | and will automatically detected                                           |
|                            | __ct mysql:restore typo3 dump.sql.bz2__                                   |
| ct mysql:convert           | Convert character set and collation of a database                         |
|                            | __ct mysql:convert typo3__ -> Convert typo3 into UTF-8 with utf8_general_ci |
|                            | __ct mysql:convert typo3 --charset=latin1__ -> Convert typo3 into LATIN-1 |
|                            | __ct mysql:convert typo3 --collation=utf8_unicode_ci__ -> Convert typo3 into UTF-8 with utf8_unicode_ci |
|                            | __ct mysql:convert typo3 --stdout__ -> Print sql statements to stdout     |

### Sync commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct sync:init               | Create example clisync.yml in current working directory                   |
| ct sync:backup             | Search for clisync.yml in tree and start backup to shared server          |
| ct sync:restore            | Search for clisync.yml in tree and start restore from shared server       |
| ct sync:server             | Search for clisync.yml in tree and start server synchronization (eg. from live or preview to local development instance  |
|                            | __ct sync:server production__ -> Use "production" configuration and start sync |
|                            | __ct sync:server preview --rsync__ -> Use "preview" configuration and start only rsync |
|                            | __ct sync:server staging --mysql__ -> Use "staging" configuration and start only mysql sync |

### PHP commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct php:trace               | Trace syscalls from one or all PHP processes (strace)                     |
|                            | __ct php:trace --all__ -> Trace all php processes immediately             |
| ct php:composer            | Search for "composer.yml" in tree and start "composer" in this directory  |

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

## Credits

Thanks to my colleagues at [cron IT GmbH](http://www.cron.eu/) and Ingo Pfennigstorf for testing and some usefull ideas.
