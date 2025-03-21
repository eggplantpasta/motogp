#!/bin/bash

# Set the PHP_ROOT as the root level of the git repo
ROOT_DIR=$(git rev-parse --show-toplevel)

xdg-open http://localhost:8080

# Start the server
php -S localhost:8080 -c ${ROOT_DIR}/config/php.ini -t ${ROOT_DIR}/public

