#!/bin/bash

# Get the absolute path of the current working directory's parent
parent_dir=$(dirname $(dirname "$PWD"))

# Define the absolute paths of the folders to include
folders=(
    "$parent_dir/backend"
    "$parent_dir/dmz"
    "$parent_dir/frontend"
    "$parent_dir/deployment"
    "$parent_dir/installScripts"
)

# Define the base output directory for the archives
output_dir="$parent_dir/zDeploy/upload"

# Ensure the target directory exists
mkdir -p "$output_dir"

# Create separate archives for each folder
for folder in "${folders[@]}"; do
    if [[ -d "$folder" ]]; then
        folder_name=$(basename "$folder")
        output_archive="$output_dir/$folder_name.tar.gz"

        echo "Creating archive for $folder at $output_archive..."
        tar -czvf "$output_archive" -C "$parent_dir" "$folder_name"

        if [[ $? -eq 0 ]]; then
            echo "Archive for $folder created successfully."
        else
            echo "Error: Failed to create archive for $folder." >&2
            exit 1
        fi
    else
        echo "Warning: $folder does not exist and will be skipped." >&2
    fi
done

echo "All archives created successfully in $output_dir."
