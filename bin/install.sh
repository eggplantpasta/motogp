#!/bin/bash

# Set the PHP_ROOT environment variable to the root level of git repo
export ROOT_DIR=$(git rev-parse --show-toplevel)

# create config.php and php.ini files from the examples
cd ${ROOT_DIR}/config

\cp -rf php-example.ini php.ini
\cp -rf config-example.php config.php

# edit in place to replace template variables
sed  -i "s@{{ROOT_DIR}}@${ROOT_DIR}@" php.ini
sed  -i "s@{{ROOT_DIR}}@${ROOT_DIR}@" config.php

# Create the sample database
cd ${ROOT_DIR}/db/

sqlite3 db.sqlite3 < reset.sql
sqlite3 db.sqlite3 < create.sql
sqlite3 db.sqlite3 < seed.sql