#!/bin/bash
set -e

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Get the project root (parent of scripts directory)
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Loop through all PHP files in the examples directory
for example in "$PROJECT_ROOT"/examples/*.php; do
    example_name=$(basename "$example")
    echo "Running $example_name..."
    php "$example" > /dev/null || { echo "ERROR: $example_name failed"; exit 1; }
done

echo "✓ All example scripts executed successfully"
