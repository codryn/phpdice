#!/bin/bash
set -e

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Get the project root (parent of scripts directory)
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Enable nullglob to handle case where no .php files exist
shopt -s nullglob

# Get all PHP files in the examples directory
php_files=("$PROJECT_ROOT"/examples/*.php)

# Check if any examples were found
if [ ${#php_files[@]} -eq 0 ]; then
    echo "ERROR: No .php files found in examples directory"
    exit 1
fi

# Loop through all PHP files in the examples directory
for example in "${php_files[@]}"; do
    example_name=$(basename "$example")
    echo "Running $example_name..."
    php "$example" > /dev/null || { echo "ERROR: $example_name failed"; exit 1; }
done

echo "✓ All example scripts executed successfully"
