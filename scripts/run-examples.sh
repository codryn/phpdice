#!/bin/bash
set -e

# Loop through all PHP files in the examples directory
for example in examples/*.php; do
    example_name=$(basename "$example")
    echo "Running $example_name..."
    php "$example" > /dev/null || { echo "ERROR: $example_name failed"; exit 1; }
done

echo "✓ All example scripts executed successfully"
