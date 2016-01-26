[<-- Back to main section](../README.md)

# Usage of `ct mysql:...`

# Common commands

```bash
# Create database typo3 (recreate and clears database if exists)
ct mysql:create typo3

# Drop database typo3
ct mysql:drop typo3

# List databases with statistics
ct mysql:list
```

# Debugging

The `ct mysql:querylog` and `ct mysql:slowlog` provides a convenient way to access the general query log 
and the slow log.

In the query log you can see all queries send and executed by the MySQL database.
The slow query log can be used to see long running queries.

```bash
# Enable and show query log
ct mysql:querylog

# Enable and show query log
ct mysql:slowlog

# Enable and show query log for all queries running longer than 1 sec
ct mysql:slowlog --time=1

# Enable and show query log for all queries which don't uses indizes
ct mysql:slowlog --no-index

```

# Backup database
You can easily backup a MySQL database (including compression compressions) and a filter set for tables.

```bash
# Backup typo3 database (without compression)
ct mysql:backup typo3 dump.sql

# Backup typo3 database (with gzip)
ct mysql:backup typo3 dump.sql.gz

# Backup typo3 database (with bzip2)
ct mysql:backup typo3 dump.sql.bz2

# Backup typo3 database (with LZMA/xz)
ct mysql:backup typo3 dump.sql.xz
```

# Restore database

Restoring is as easy as backuping a database, the `ct mysql:restore` will drop the database (if exists), 
recreate it and restores the dump into the database. With this workflow you also removes all tables which 
are not part of the dump file - it's a clean restore of the dump.
Also the compression is automatically detected by file mime type.

```bash
# Restore typo3 database (auto compression detection)
ct mysql:restore typo3 dump.sql
```

# Database charset conversion

```bash
# Convert database to UTF8
ct mysql:convert typo3

# Convert database typo3 to UTF8 and collation utf8_unicode_ci
ct mysql:convert typo3 --collation=utf8_unicode_ci

# Convert database typo3 to Latin1
ct mysql:convert typo3 --charset=latin1

# Convert database typo3 to Latin1, only show queries
ct mysql:convert typo3 --stdout
```





