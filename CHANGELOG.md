CliTools Changelog
==================

1.10.0 - Upcoming
------------------
- Added GitHub based `self-update`
- Added `make` (auto search for Makefile in tree)
- Added `php:composer` (auto search for composer.yml in tree)
- Fixed some issues

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

1.7.4 - 2015-04-21
------------------
- Improved `docker:tshark`

1.7.3 - 2015-04-21
------------------
- Fixed `docker:tshark`

1.7.2 - 2015-04-21
------------------
- Added required php modules checks
- Added interactive error return code check

1.7.0 - 2015-04-19
------------------
- Added `docker:tshark`, easy network sniffing
- Added `php:trace --all`, for immediate tracing all php processes
- Fixed bugs

1.6.3 - 2015-04-16
------------------
- Added `docker:tshark`, easy network sniffing
- Added `php:trace --all`, for immediate tracing all php processes
- Fixed bugs

1.6.2 - 2015-04-15
------------------
- Fixed bugs

1.5.1 - 2015-03-29
------------------
- Added growl support
