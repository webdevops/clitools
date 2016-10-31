[<-- Back to main section](../README.md)

# Usage `ct sync:...`

## Init and configuration of `sync`

With the `sync` commands you can update your local development installation to the current state of your
server installations. Currently filesync (rsync) and database fetching is supported.

First you need to create a `clisync.yml` in your project directory, the CliTools will provide your an example
of this file by using following command:

```bash
ct sync-init
```

If you need special SSH settings (ports, compression, identify...) please use your `~/.ssh/config` file 
to such settings.

You can commit this clisync.yml into your project so other developers can use the sync feature, too.

## Synchronisation with servers (ct sync server)

The synchronisation with your servers is one way only, it just syncs your server installation to your 
local installation (CliTools are no deployment tools!).

In the `clisync.yml` you can specify multiple servers.

Now you can sync your `production` server to your local installation:

```bash
# Full sync (files and database, with interactive context selection)
ct sync

# Only MySQL (from production context, without interactive context selection)
ct sync production --mysql

# Only Files (from production context, without interactive context selection)
ct sync production --rsync
```

## Project sharing  (ct share:backup and ct share:restore)

The sharing can be used to share files (assets) and databases between developers.
Please use a common development/storage server with ssh access for each developer for this feature.

```bash
# Make backup of current state and transfer to share server
ct share:backup

# ... only MySQL
ct share:backup --mysql

# ... only files
ct share:backup --rsync

# Restore to state from the share server
ct share:restore

# ... only MySQL
ct share:restore --mysql

# ... only files
ct share:restore --rsync

```

## Lightweight deployment (ct deploy server)

With `deploy` you can push your files to your production servers.
Please keep in mind that this feature is just an wrapped rsync and should only be
the simplest solution for deployment. For more advanced or centralized deployment try
solutions build on Jenkis, Ansible and others.

```bash
# Push your project to your servers (with interactive context selection)
ct deploy

# Push your project to your staging server (without interactive context selection)
ct deploy staging
```

## Rsync additional options (rsync.opts)

The rsync options `-rlptD --delete-after --progress --human-readable` are automatically added when using the sync
features of clitools. In addition, it is possible to set more options by adding them via `rsync.opts` (available since
version 2.3 of clitools). In the following example the option `--iconv=UTF8-MAC,UTF8` is added to enable rsync filename
charset conversion between local and remote file system. You need this especially when using rsync between hosts where a
charset conversion on filenames is necessary (see `man rsync` for further details; e.g.: you probably need this when
you sync between mac and linux utf-8 filesystem).

```
...
# Enable filename charset conversion where local is UTF8-MAC (Mac OSX) and remote is UTF8 (e.g. Linux)
rsync.opts: "--iconv=UTF8-MAC,UTF8"
...
```

If you run into problems with `--iconv` make shure you have a compatible version of rsync >= 3.0 (as already mentioned
in the [install section](INSTALL.md) of this documentation).

## Advanced ssh options

If you need some advanced ssh options (eg. other ports) use your `~/.ssh/config` configuration file:

    Host project-server
        Hostname project-server.example.com
        Port     12345
        User     root

If you have a proxy server you can configure it like this:

    Host ssh-proxy
        Hostname ssh-proxy.example.com
        User foo

    Host project-server
        Hostname project-server.example.com
        Port     12345
        User     root
        ProxyCommand ssh ssh-proxy -W %h:%p


Now you can use `project-server` as ssh-hostname and your settings will automatically used from your `~/.ssh/config`.
