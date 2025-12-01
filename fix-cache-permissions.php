<?php

/**
 * Fix cache directory permissions
 *
 * This script creates the cache directory structure with proper permissions
 * for the cmis-test user.
 */

$baseDir = __DIR__ . '/storage/framework/cache/data';

echo "Fixing cache directory permissions...\n";

// Create all hex subdirectories (00-ff)
$hexChars = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];

$created = 0;
$failed = 0;

foreach ($hexChars as $first) {
    foreach ($hexChars as $second) {
        $dir = "$baseDir/{$first}{$second}";

        if (!is_dir($dir)) {
            if (@mkdir($dir, 0775, true)) {
                $created++;
                echo ".";
            } else {
                $failed++;
                echo "F";
            }
        } else {
            // Directory exists, try to make it writable
            if (@chmod($dir, 0775)) {
                echo ".";
            } else {
                echo "!";
            }
        }
    }
}

echo "\n\n";
echo "Summary:\n";
echo "Created: $created directories\n";
echo "Failed: $failed directories\n";

if ($failed > 0) {
    echo "\nNote: Some directories couldn't be created. You may need to run:\n";
    echo "sudo chown -R cmis-test:cmis-test storage/framework/cache\n";
    echo "sudo chmod -R 775 storage/framework/cache\n";
    exit(1);
} else {
    echo "\nCache directory structure fixed successfully!\n";
    exit(0);
}
