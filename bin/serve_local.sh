#!/bin/bash

# Set the PHP_ROOT as the root level of the git repo
ROOT_DIR=$(git rev-parse --show-toplevel)

# MacOS compatability
if command -v xdg-open &> /dev/null; then
    xdg-open http://localhost:8080
else
    open http://localhost:8080
fi


# Start the server
php -S localhost:8080 -c ${ROOT_DIR}/config/php.ini -t ${ROOT_DIR}/public

