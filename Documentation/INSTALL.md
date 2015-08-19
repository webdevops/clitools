q[<-- Back to main section](../README.md)

# Installation

## Requirements

- PHP 5.5 (CLI) with pcntl module
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


## Install clitools

```bash
# Download latest tools (or in ~/bin if you have it in $PATH)
wget -O/usr/local/bin/ct https://www.achenar.net/clicommand/clitools.phar

# Set executable bit
chmod 777 /usr/local/bin/ct

# MacOS/Linux: example configuration for Docker VM
wget -O"$HOME/.clitools.ini" https://github.com/webdevops/clitools/blob/develop/Documentation/Examples/macos-docker-clitools.ini
```

## Aliases

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

The [default configuration](https://github.com/webdevops/clitools/blob/develop/src/config.ini) is inside the phar.

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

## Update clitools

```bash
# Stable channel
ct self-udpate

## Beta channel
ct self-update --beta

## Fallback update (if GitHub fails)
ct self-update --fallback
```



## Install clitools from source (You don't have to perform any tasks of the default installation procedure)

```bash
# Clone the repository
git clone https://github.com/webdevops/clitools clitools

# cd into cloned repository
cd clitools

# run all makefile tasks which are necessary for building and installing from source
make all
```

If you take a look into the `Makefile`, you will see which tasks have been executed.
The steps are:

1. Execute composer command
2. Start main build script
3. Copy the previously built phar file to `/usr/local/bin`, so that you can execute clitools just by typing ct.
