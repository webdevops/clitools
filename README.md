# CliTools for Vagrant VM

Documentation is still WIP :)

## Special commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct self-update             | Update ct command (download new version)                                  |
| ct update                  | Updates all system components, ssh configuration, ct command update etc.  |

## System commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct shutdown (alias)        | Shutdown system                                                           |

## Log commands

All log commands are using a grep-filter (specified as optional argument)

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct log:mail                | Shows mail logs                                                           |

## MySQL commands

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
| ct mysql:drop              | Drops a database                                                          |
|                            | __ct mysql:drop typo3__                                                   |
| ct mysql:list              | Lists all databases with some statitics                                   |
| ct mysql:restart           | Restart MySQL server                                                      |
| ct mysql:restore           | Create (and drops if already exists) a database and restore from a dump   |
|                            | Dump file can be plaintext, gziped or bzip2 compressed                    |
|                            | __ct mysql:restore typo3 dump.sql.bz2__                                   |

## PHP commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct php:trace               | Trace syscalls from one or all PHP processes (strace)                     |


## Samba commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct samba:restart           | Restart Samba server                                                      |


## System commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct system:env              | Lists common environment variables                                        |
| ct system:openfiles        | Lists current open files count grouped by process                         |
| ct system:shutdown         | Shutdown system                                                           |
| ct system:swap             | Show swap usage for running processes                                     |
| ct system:update           | Updates all system components, ssh configuration, ct command update etc.  |
| ct system:version          | Shows version for common packages                                         |

## TYPO3 commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct typo3:beuser            | Injects a dev user (pass dev) to all or one specified TYPO3 database      |
|                            | __ct typo3:beuser__                                                       |
|                            | __ct typo3:beuser typo3__                                                 |
| ct typo3:cleanup           | Cleanup command tables to same some table space                           |
|                            | __ct typo3:cleanup__                                                      |
|                            | __ct typo3:cleanup typo3__                                                |

## User commands

| Command                    | Description                                                               |
|----------------------------|---------------------------------------------------------------------------|
| ct user:rebuildsshconfig   | Rebuild SSH config from ct repository (/vagrant/provision/sshconfig)      |

