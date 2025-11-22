#!/usr/bin/env php
<?php
/**
 * Add missing closing braces to model files
 */

$modelsPath = dirname(__DIR__) . '/app/Models';
$fixed = 0;

function fixFile(string $filePath): bool
{
    $content = file_get_contents($filePath);
    $trimmed = rtrim($content);

    // If file doesn't end with }, add it
    if (!str_ends_with($trimmed, '}')) {
        $content = $trimmed . "\n}\n";
        file_put_contents($filePath, $content);
        return true;
    }

    return false;
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modelsPath)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        if (fixFile($file->getPathname())) {
            echo "✅ Fixed: {$file->getPathname()}\n";
            $fixed++;
        }
    }
}

echo "\n✅ Added closing braces to {$fixed} files\n";
