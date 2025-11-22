#!/bin/bash
# Cleanup script for boot() method remnants

find app/Models -name "*.php" -type f | while read file; do
    # Remove orphaned closing parentheses and braces patterns that are left over from boot() removal
    # Pattern: whitespace, closing paren/brace, semicolon on separate lines
    sed -i '/^[[:space:]]*);$/d' "$file"
    sed -i '/^[[:space:]]*}$/N;s/^\([[:space:]]*\)}\n\1}/\1}/g' "$file"

    # Clean up multiple consecutive blank lines (more than 2)
    sed -i '/^$/N;/^\n$/D' "$file"
done

echo "✅ Cleanup complete!"
