#!/usr/bin/env bash
# this script works on ubuntu 13.10 default environment,
# on other systems you may have to change the sqlite command!

# dump full db (schema+data) to temporary dump file:
sqlite3 db/test.db .dump > /tmp/test.db.dump
# extract only data from dump file and store to sql script:
cat /tmp/test.db.dump | grep '^INSERT INTO' > sql/testdata.sqlite.sql


