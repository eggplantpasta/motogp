#!/bin/bash

# Set the PHP_ROOT environment variable to the root level of git repo
export ROOT_DIR=$(git rev-parse --show-toplevel)

# create config.php file from the example
cd ${ROOT_DIR}/config

\cp -rf config-example.php config.php

# Create the sample database
${ROOT_DIR}/db/database-reset.sh
