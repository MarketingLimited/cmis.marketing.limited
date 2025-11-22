#!/usr/bin/env php
<?php
/**
 * Fix missing closing braces in methods
 * This fixes methods that are missing their closing }
 */

$modelsPath = dirname(__DIR__) . '/app/Models';
$fixed = 0;

function fixFile(string $filePath): bool
{
    $content = file_get_contents($filePath);
    $originalContent = $content;

    // Pattern: method definition without closing brace before next method/comment
    // Look for patterns like:
    // public function foo() {
    //     return ...;
    // (missing })
    // /**
    // or
    // public function bar() {

    $lines = explode("\n", $content);
    $newLines = [];
    $inMethod = false;
    $braceCount = 0;
    $methodStartLine = -1;

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        $trimmed = trim($line);

        // Detect method start
        if (preg_match('/^\s*(public|protected|private)\s+function\s+\w+\s*\(/', $line)) {
            if ($inMethod && $braceCount > 0) {
                // Previous method wasn't closed, add closing brace
                $newLines[] = '    }';
                $newLines[] = '';
            }
            $inMethod = true;
            $braceCount = 0;
            $methodStartLine = $i;
        }

        // Count braces
        $braceCount += substr_count($line, '{');
        $braceCount -= substr_count($line, '}');

        // If we hit another method/doc comment and braces aren't balanced
        if ($inMethod && $braceCount > 0) {
            if (preg_match('/^\s*\/\*\*/', $line) ||
                preg_match('/^\s*(public|protected|private)\s+function/', $line) ||
                preg_match('/^\s*}$/', $line) && $i > $methodStartLine + 3) {
                // Add missing closing brace before this line
                $newLines[] = '    }';
                $newLines[] = '';
                $braceCount = 0;
                $inMethod = false;
            }
        }

        if ($braceCount <= 0 && $inMethod) {
            $inMethod = false;
        }

        $newLines[] = $line;
    }

    // If still in method at end of file
    if ($inMethod && $braceCount > 0) {
        $newLines[] = '    }';
    }

    $content = implode("\n", $newLines);

    if ($content !== $originalContent) {
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

echo "\n✅ Fixed method braces in {$fixed} files\n";
