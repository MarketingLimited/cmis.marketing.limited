#!/usr/bin/env php
<?php
/**
 * Fix all methods missing closing braces
 * Detects "unexpected token public" errors and adds missing }
 */

$modelsPath = dirname(__DIR__) . '/app/Models';
$fixed = 0;

function hasMethodBraceError(string $filePath): bool
{
    exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output);
    $result = implode("\n", $output);
    return str_contains($result, 'unexpected token "public"') ||
           str_contains($result, 'Unclosed');
}

function fixMethodBraces(string $filePath): bool
{
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $newLines = [];

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        $nextLine = $lines[$i + 1] ?? '';

        // If current line has a return/statement but next line is a doc comment or method
        // and we're missing a closing brace
        if ((str_contains($line, 'return ') || str_contains($line, '->where(')) &&
            !str_contains($line, ');') &&
            (str_contains($nextLine, '/**') || str_contains($nextLine, 'public function') || str_contains($nextLine, 'protected function'))) {
            // Add closing brace
            $newLines[] = rtrim($line);
            $newLines[] = '    }';
            $newLines[] = '';
            continue;
        }

        $newLines[] = $line;
    }

    file_put_contents($filePath, implode("\n", $newLines));

    // Check if it fixed the error
    return !hasMethodBraceError($filePath);
}

// Find all PHP files with errors
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modelsPath)
);

$filesToFix = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        if (hasMethodBraceError($file->getPathname())) {
            $filesToFix[] = $file->getPathname();
        }
    }
}

echo "Found " . count($filesToFix) . " files with method brace errors\n\n";

foreach ($filesToFix as $filePath) {
    echo "Fixing: $filePath\n";
    if (fixMethodBraces($filePath)) {
        echo "✅ Fixed successfully\n";
        $fixed++;
    } else {
        echo "⚠️ Still has issues, may need manual fix\n";
    }
}

echo "\n✅ Fixed $fixed files\n";
