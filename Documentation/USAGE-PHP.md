[<-- Back to main section](../README.md)

# Usage `ct php:...`

## Composer (auto searching in path tree)

Because you always need to jump into `composer.json` directory `ct php:composer` will do this for you

```bash
# Run composer install task
ct php:composer install

# Run composer update task
ct php:composer update
```

Hint: You can use `alias composer='ct php:composer'` for this.


## Sys-Tracing PHP Processes

Because strace'ing already running processes requires some shell knowledge `ct php:trace` will make this handy for you.

```bash
# Trace one or all php processes (interactive mode)
ct php:trace

# Trace all processes immediately
ct php:trace --all
```



