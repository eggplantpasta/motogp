#!/bin/bash
set -e

# Set the PHP_ROOT environment variable to the root level of git repo
export ROOT_DIR=$(git rev-parse --show-toplevel)

# create config.ini and php.ini files from the examples
cd ${ROOT_DIR}/config

\cp -rf php-example.ini php.local.ini
\cp -rf app-example.ini app.local.ini

# edit in place to replace template variables
sed  -i.bak "s@{{ROOT_DIR}}@${ROOT_DIR}@" php.local.ini
sed  -i.bak "s@{{ROOT_DIR}}@${ROOT_DIR}@" app.local.ini
rm php.local.ini.bak app.local.ini.bak

# Create the sample database
cd ${ROOT_DIR}/db
./reset.sh

# Install composer packages
composer install --working-dir=${ROOT_DIR}
