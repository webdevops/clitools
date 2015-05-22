[<-- Back to main section](../README.md)

# Usage

## SYNC: Project synchronatizon

With the `sync` commands you can update your local development installation to the current state of your
server installations. Currently filesync (rsync) and database fetching is supported.

First you need to create a `clisync.yml` in your project directory, the CliTools will provide your an example
of this file by using following command:

	ct sync:init

If you need special SSH settings (ports, compression, identify...) please use your `~/.ssh/config` file 
to such settings.

You can commit this clisync.yml into your project so other developers can use the sync feature, too.

### Synchronisation with servers (ct sync:server)

The synchronisation with your servers is one way only, it just syncs your server installation to your 
local installation (CliTools are no deployment tools!).

In the `clisync.yml` you can specify multiple servers.

Now you can sync your `production` server to your local installation:

	# Full sync (files and database)
	ct sync:server production

	# Only MySQL
	ct sync:server production --mysql

	# Only Files
	ct sync:server production --files

## Project sharing  (ct sync:backup and ct sync:restore)

The sharing can be used to share files (assets) and databases between developers.
Please use a common development/storage server with ssh access for each developer for this feature.


	# Make backup of current state and transfer to share server
	ct sync:backup

	# Restore to state from the share server
	ct sync:restore


