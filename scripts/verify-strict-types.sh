#!/bin/bash
set -e

echo "Checking for strict_types in all PHP files..."
MISSING=0
for file in $(find src tests -name "*.php"); do
if ! grep -q "declare(strict_types=1);" "$file"; then
    echo "ERROR: Missing strict_types in $file"
    MISSING=$((MISSING + 1))
fi
done
if [ $MISSING -gt 0 ]; then
echo "ERROR: $MISSING file(s) missing strict_types declaration"
exit 1
fi
echo "✓ All PHP files have strict_types declarations"
