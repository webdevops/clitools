[<-- Back to main section](../README.md)

# Usage of `ct docker:...`

## Docker creation

You can easly create new docker instances (from my or a custom docker boilerplate) also with code intalization
and Makefile running.

```bash
# Startup new docker boilerplate
ct docker:create

# Startup new custom docker boilerplate 
ct docker:create --docker=git...

# Startup new docker boilerplate with code repository
ct docker:create --code=git...

# Startup new docker boilerplate with code repository and makefile run
ct docker:create --code=git... --make=build
```

## Docker startup

The `docker:up` command will search the `docker-compose.yml` in the current parent directroy tree and
execute `docker-compose` from this directroy - you don't have to change the current directroy.

Also the previous docker instance will be shut down to avoid port conflicts.

```bash
# Startup docker-compose
ct docker:up
```

## Custom docker commands

As `docker:up` the `docker:compose` will search the `docker-compose.yml` and will execute your command
from this directroy.

```bash
# Stop docker instance
ct docker:compose stop

# Show docker container status
ct docker:compose ps
```

Hint: You can use `alias dcc='ct docker:compose'` for this.

## Docker shell access

There are many ways to jump into docker containers:

```bash
# Jump into a root shell
ct docker:root 

# Jump into a root shell in mysql container
ct docker:root mysql

# Jump into a user shell (defined by CLI_USER as docker env)
ct docker:shell 

# Jump into a root user in mysql container (defined by CLI_USER as docker env)
ct docker:root mysql
```

## Docker command execution

```bash
# Execute command "ps" in "main" container
ct docker:exec ps 
```

## Docker cli execution

You can define a common CLI script entrypoint with the environment variable CLI_SCRIPT in your docker containers.
The environment variable will be read by `ct docker:cli` and will be executed - you don't have to jump
into your containers, you can start your CLI_SCRIPTs from the outide.

```bash
# Execute predefined cli command with argument "help" in "main" container
ct docker:cli help
```

## Docker debugging

If you want to debug a docker application (eg. your webpage inside docker) the `ct docker:sniff` provides you
a network sniffer set for various protocols (eg. http or mysql).

```bash
# Show basic http traffic
ct docker:sniff http 

# Show full http traffic
ct docker:sniff http --full

# Show mysql querys by using network sniffer
ct docker:sniff mysql 
```
