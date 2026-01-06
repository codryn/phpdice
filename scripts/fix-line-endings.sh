#!/bin/bash

# Script to fix line endings in code files
# Converts Windows (CRLF) to Unix (LF) line endings

echo "🔧 Fixing Line Endings in Code Files..."
echo ""

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counter for files
total_files=0
fixed_files=0
skipped_files=0

# Function to check if file has CRLF line endings
has_crlf() {
    local file="$1"
    # Check if file contains carriage returns using grep
    if grep -qU $'\r' "$file" 2>/dev/null; then
        return 0
    fi
    return 1
}

# Function to convert file to LF line endings
convert_to_lf() {
    local file="$1"

    # Use dos2unix if available, otherwise use sed
    if command -v dos2unix &> /dev/null; then
        dos2unix "$file" 2>/dev/null
    else
        # Use sed as fallback
        sed -i 's/\r$//' "$file"
    fi
}

# Function to process a file
process_file() {
    local file="$1"

    ((total_files++))

    # Skip binary files and vendor/node_modules
    if [[ "$file" =~ vendor/ ]] || [[ "$file" =~ node_modules/ ]]; then
        ((skipped_files++))
        return
    fi

    # Check if file has CRLF
    if has_crlf "$file"; then
        echo -e "${YELLOW}Converting:${NC} $file"
        convert_to_lf "$file"
        ((fixed_files++))
    fi
}

# Find and process all code files
echo "Scanning for files with CRLF line endings..."
echo ""

# Shell scripts
while IFS= read -r -d '' file; do
    process_file "$file"
done < <(find . -type f \( -name "*.sh" \) -print0)

# PHP files
while IFS= read -r -d '' file; do
    process_file "$file"
done < <(find . -type f \( -name "*.php" \) -print0)

# HTML files
while IFS= read -r -d '' file; do
    process_file "$file"
done < <(find . -type f \( -name "*.html" -o -name "*.htm" \) -print0)

# JavaScript files
while IFS= read -r -d '' file; do
    process_file "$file"
done < <(find . -type f \( -name "*.js" -o -name "*.mjs" \) -print0)

# CSS files
while IFS= read -r -d '' file; do
    process_file "$file"
done < <(find . -type f \( -name "*.css" \) -print0)

# SQL files
while IFS= read -r -d '' file; do
    process_file "$file"
done < <(find . -type f \( -name "*.sql" \) -print0)

# XML/Config files
while IFS= read -r -d '' file; do
    process_file "$file"
done < <(find . -type f \( -name "*.xml" -o -name "*.json" -o -name "*.yml" -o -name "*.yaml" \) -print0)

# Markdown files
while IFS= read -r -d '' file; do
    process_file "$file"
done < <(find . -type f \( -name "*.md" \) -print0)

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}✅ Line Endings Fix Complete!${NC}"
echo ""
echo "Summary:"
echo "  Total files scanned: $total_files"
echo -e "  ${GREEN}Files converted:${NC} $fixed_files"
echo "  Files skipped: $skipped_files"
echo "  Already LF: $((total_files - fixed_files - skipped_files))"
echo ""

if [ $fixed_files -gt 0 ]; then
    echo -e "${YELLOW}Note:${NC} $fixed_files file(s) were converted from CRLF to LF"
    echo "You may want to commit these changes."
else
    echo -e "${GREEN}All files already use Unix (LF) line endings!${NC}"
fi
