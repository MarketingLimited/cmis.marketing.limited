#!/usr/bin/env python3
"""
Convert deprecated PHPUnit @test annotations to #[Test] attributes.
This script processes all test files to fix PHPUnit 11 deprecations.
"""

import os
import re
from pathlib import Path

def convert_file(filepath):
    """Convert a single test file from @test to #[Test]."""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    original_content = content

    # Check if file has @test annotations
    if '/** @test */' not in content and '/**@test*/' not in content:
        return False

    # Check if Test attribute import already exists
    has_test_import = 'use PHPUnit\\Framework\\Attributes\\Test;' in content

    # Add import if needed
    if not has_test_import:
        # Find the last 'use' statement before the class declaration
        use_pattern = r'((?:use [^;]+;[\r\n]+)+)'
        match = re.search(use_pattern, content)

        if match:
            # Add the import after existing use statements
            last_use_end = match.end()
            content = (
                content[:last_use_end] +
                'use PHPUnit\\Framework\\Attributes\\Test;\n' +
                content[last_use_end:]
            )

    # Replace @test annotations with #[Test] attributes
    # Handle various whitespace patterns
    content = re.sub(r'    /\*\* @test \*/\s*\n', '    #[Test]\n', content)
    content = re.sub(r'    /\*\*@test\*\/\s*\n', '    #[Test]\n', content)
    content = re.sub(r'    /\*\*\s*@test\s*\*/\s*\n', '    #[Test]\n', content)

    # Only write if content changed
    if content != original_content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        return True

    return False

def main():
    """Process all test files."""
    test_dir = Path('tests')
    files_processed = 0
    files_modified = 0

    print("Starting PHPUnit @test to #[Test] conversion...")
    print("-" * 60)

    # Find all PHP test files
    for php_file in test_dir.rglob('*.php'):
        files_processed += 1
        if convert_file(php_file):
            files_modified += 1
            print(f"✓ Modified: {php_file}")

    print("-" * 60)
    print(f"Processed: {files_processed} files")
    print(f"Modified: {files_modified} files")
    print("Conversion complete!")

if __name__ == '__main__':
    main()
