[<-- Back to main section](../README.md)

## Shell aliases

```bash
# Shortcut for docker-compose (autosearch docker-compose.yml in up-dir, you don't have to be in directory with docker-compose.yml)
alias dcc='ct docker:compose'

# Startup docker-container
alias dccup='ct docker:up'
# Startup docker-container and shutdown previous one
alias dccswitch='ct docker:up --switch'
# Stop docker-container
alias dccstop='ct docker:compose stop'

# Enter main docker container (as CLI_USER if available - if not specified then root is used)
alias dcshell='ct docker:shell'
alias dcsh='ct docker:shell'

# Enter main docker container (as root)
alias dcroot='ct docker:root'

# Execute predefined cli in docker container
alias dccrun='ct docker:cli'

# Run command
alias dcexec='ct docker:exec'

# Execute mysql client in docker container
alias dcsql='ct docker:mysql'
alias dcmysql='ct docker:mysql'

# General shortcuts (with up-dir tree searching)
alias composer='ct php:composer'
alias make='ct make'
```
