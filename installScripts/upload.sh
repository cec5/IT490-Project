#!/bin/bash

# Get the absolute path of the current working directory's parent
parent_dir=$(dirname $(dirname "$PWD"))

# Define the absolute paths of the folders to include
folders=(
    "$parent_dir/backend"
    "$parent_dir/dmz"
    "$parent_dir/frontend"
    "$parent_dir/zDeploy"
    "$parent_dir/deployment"
)

# Define the output archive location
output_archive="$parent_dir/zDeploy/upload/sendoff.tar.gz"

# Ensure the target directory exists
mkdir -p "$(dirname "$output_archive")"

# Create the archive, filtering out non-existent folders
echo "Creating archive at $output_archive..."
tar_command="tar -czvf \"$output_archive\""

for folder in "${folders[@]}"; do
    if [[ -d "$folder" ]]; then
        tar_command+=" -C \"$parent_dir\" \"$(basename "$folder")\""
    else
        echo "Warning: $folder does not exist and will be skipped." >&2
    fi
done

# Execute the tar command
eval $tar_command
tar_exit_code=$?

# Check if tar succeeded
if [[ $tar_exit_code -eq 0 ]]; then
    echo "Archive created successfully at $output_archive."
else
    echo "Warning: Archive creation encountered issues but may still have completed."
    exit 1
fi
