#!/usr/bin/env bash
# this script works on ubuntu 13.10 default environment,
# on other systems you may have to change the sqlite command!

# extract and store db schema to sql script:
sqlite3 db/nouron.db .sch > sql/schema.sqlite.sql
# dump full db (schema+data) to temporary dump file:
sqlite3 db/nouron.db .dump > /tmp/nouron.db.dump
# extract only data from dump file and store to sql script:
cat /tmp/nouron.db.dump | grep '^INSERT INTO' > sql/data.sqlite.sql


