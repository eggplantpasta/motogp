#!/bin/bash

# filepath: /home/brett/git-repos/motogp/bin/deploy-do.sh

# Define the source and destination directories
SOURCE_DIR="/home/brett/git-repos/motogp"
DEST_DIR="/var/www/motogp.pudiga.org"

# Directories to copy
DIRECTORIES=("config" "db" "public" "src")

# Ensure the destination directory exists
if [ ! -d "$DEST_DIR" ]; then
    echo "Creating destination directory: $DEST_DIR"
    mkdir -p "$DEST_DIR"
fi

# Copy each directory
for DIR in "${DIRECTORIES[@]}"; do
    if [ -d "$SOURCE_DIR/$DIR" ]; then
        echo "Copying $DIR to $DEST_DIR"
        cp -rf "$SOURCE_DIR/$DIR" "$DEST_DIR"
    else
        echo "Warning: $SOURCE_DIR/$DIR does not exist, skipping."
    fi
done

echo "Deployment complete."
