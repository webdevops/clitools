CliTools Changelog
==================

next - UPCOMING
------------------

2.3.3 - 2016-03-03
------------------
- Added main and app containers (see `config.ini`) for `docker:exec`, `docker:shell` and `docker:cli`

2.3.2 - 2016-02-18
------------------
- Fixed issue which prevented deletion of orpahend docker images 
- SLOC: 7,034

2.3.1 - 2016-02-14
------------------
- Switched to official docker volume cleanup
- SLOC: 7,034

2.3.0 - 2016-01-26
------------------
- Updated compile documentation
- Improved compile scripts
- Added rsync.opts documentation
- Added rsync.opts section in `clisync.yml` for additional rsync options
- No terminal title if stdout redirect is detected
- Added `docker:cleanup` for cleanup of orphaned images and volumes
- Renamed `sync:server` to `sync`
- Renamed `sync:backup` to `share:backup`
- Renamed `sync:restore` to `share:restore`
- Renamed `sync:deploy` to `deploy`
- Added `COMPOSER=custom.json ct php:composer` support
- Support for new PHP and TYPO3 Docker Boilerplate (with APPLICATION_USER detection, configurable)

2.2.0 - 2015-08-19
------------------
- Introduced `docker:up --switch` (`docker:up` without --switch parameter no longer shutdown previous one)
- Reverted to old `sync` method (`--files-from` instead of `--include-from`)
- Added protocol `php-fpm` to `docker:sniff`

2.1.5 - 2015-08-03
------------------
- Fixed `sync:...` commands with wrong rsync synchronization

2.1.4 - 2015-08-01
------------------
- Fixed "CliTools\Shell\CommandBuilder\DatabaseConnection" not found
- Fixed `typo3:domain` incorrect setting baseurl to hidden domains
- Set new fallback download url (dl.webdevops.io)

2.1.3 - 2015-07-31
------------------
- PSR2 reformatting
- Fixed mysql/mysqldump username/passwort/hostname/port setting from internal connection
- Changed github repository to webdevops organisation

2.1.2 - 2015-07-22
------------------
- Fixed smaller issues
- Rollback to Symfony 2.7.1

2.1.1 - 2015-07-17
------------------
- Fixed `php:composer` global command usage
- Fixed `system:startup` terminal title in /etc/issue
- Updated to Symfony 2.7.2
- SLOC: 6,050

2.1.0 - 2015-07-08
------------------
- Added option `docker:create --up` for automatic startup
- Fixed bugs
- SLOC: 6,014

2.0.0 - 2015-06-16
------------------
- Added GitHub based `self-update`
- Added `make` (auto search for Makefile in tree)
- Added `php:composer` (auto search for composer.json in tree)
- Added `mysql:convert` for automatic changing charset and collation of one database
- Added `sync:server` for syncing any configured server to your local development system (reads clisync.yml or .clisync.yml)
- Added `sync:backup` for backup to a shared server (reads clisync.yml or .clisync.yml)
- Added `sync:restore` for restore from a shared server (reads clisync.yml or .clisync.yml)
- Added `sync:deploy` for lightweight deployment to a foreign server (reads clisync.yml or .clisync.yml)
- Added `typo3:domain --list` for only list the domains of one or all databases
- Added `typo3:domain --remove=domain/pattern` for domain cleanup (eg. vagrant share)
- Added `typo3:domain --duplication=suffix` for domain duplication
- Added `typo3:domain --baseurl` for setting config.baseURL in SetupTS
- Added `vagrant:share` with automatic domain setting for TYPO3 projects (ALPHA! not finished!)
- TTY banner now will be reloaded (SIGHUB is send to getty tty1)
- Added docker detection for sync features
- Updated to Symfony 2.7.1
- Refactored some classes
- Fixed some issues
- Added gzip compression for PHAR
- SLOC: 5,999

1.9.0 - 2015-05-06
------------------
- Added `mysql:backup` (with --filter=typo3, support for plain sql, gzip, bzip2, lzma compression)
- Added `docker:create`, will create an new instance of [TYPO3 Docker Boilerplate](https://github.com/mblaschke/TYPO3-docker-boilerplate) (or any other docker boilerplate).
- Added `docker:up` with fast docker instance switching (will stop previous docker instance)
- Added `docker:shell --user=root` for custom user switch
- Added `docker:root` for root shell
- Added docker environment `CLI_SCRIPT` and `CLI_USER` support for `docker:shell` and `docker:cli`
- Refactored shell command execution (again)
- Fixed code styling
- Improved code and fixed some smaller bugs
- SLOC: 4,038

1.8.0 - 2015-04-26
------------------
- Added `apache:trace`
- Added `mysql:slowlog`
- Improved `mysql:debug` (alias is `mysql:querylog`)
- Added `docker:compose`, will search recursive up-dir for docker-compose.yml
- Added `docker:cli`
- Improved `docker:sniff` (was `docker:tshark`)
- Added lzma support for `mysql:restore`
- Set default method of `docker:cli` to docker-exec
- Improved docker handling
- Implemented command check
- Improved disk usage warning (wall and growl, will trigger when usage is >=90 in local and remote mounts)
- Refactored shell command execution
- SLOC: 3,562

1.7.4 - 2015-04-21
------------------
- Improved `docker:tshark`
- SLOC: 2,787

1.7.3 - 2015-04-21
------------------
- Fixed `docker:tshark`
- SLOC: 2,780

1.7.2 - 2015-04-21
------------------
- Added required php modules checks
- Added interactive error return code check
- SLOC: 2,777

1.7.0 - 2015-04-19
------------------
- Added `docker:tshark`, easy network sniffing
- Added `php:trace --all`, for immediate tracing all php processes
- Fixed bugs
- SLOC: 2,755

1.6.3 - 2015-04-16
------------------
- Added `docker:tshark`, easy network sniffing
- Added `php:trace --all`, for immediate tracing all php processes
- Fixed bugs
- SLOC: 2,832

1.6.2 - 2015-04-15
------------------
- Fixed bugs
- SLOC: 2,811

1.5.1 - 2015-03-29
------------------
- Added growl support
- SLOC: 2,773
