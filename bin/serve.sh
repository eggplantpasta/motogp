#!/bin/bash

# Set the PHP_ROOT as the root level of the git repo
ROOT_DIR=$(git rev-parse --show-toplevel)

# open the default browser to the local server
xdg-open http://localhost:8080

# Start the server
php -S localhost:8080 -c ${ROOT_DIR}/public/.user.ini -t ${ROOT_DIR}/public

