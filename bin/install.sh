#!/bin/bash

# Set the PHP_ROOT environment variable to the root level of git repo
export ROOT_DIR=$(git rev-parse --show-toplevel)

# create config.ini and php.ini files from the examples
cd ${ROOT_DIR}/config

\cp -rf php-example.ini php.ini
\cp -rf app-example.ini app.ini

# edit in place to replace template variables
sed  -i '' "s@{{ROOT_DIR}}@${ROOT_DIR}@" php.ini
sed  -i '' "s@{{ROOT_DIR}}@${ROOT_DIR}@" app.ini

# Create the sample database
cd ${ROOT_DIR}/db
./reset.sh
