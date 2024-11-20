#!/bin/bash

# Get the name of the current folder
current_folder=$(basename "$PWD")

# Define valid folder names
valid_folders=("backend" "dmz" "frontend" "zDeploy" "deployment")

# Check if the current folder name is valid
if [[ ! " ${valid_folders[@]} " =~ " ${current_folder} " ]]; then
    echo "Error: Script must be run in a folder named 'backend', 'dmz', or 'frontend'."
    exit 1
fi

# Set the archive name to include the current folder name
archive_name="${current_folder}_deployment.tar.gz"

# Compress all files in the directory except this script
tar --exclude="./$(basename "$0")" -czvf "$archive_name" ./*

echo "Archive created as $archive_name in $current_folder directory."