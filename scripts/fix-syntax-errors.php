#!/usr/bin/env php
<?php
/**
 * Fix syntax errors left from boot() method removal
 */

$modelsPath = dirname(__DIR__) . '/app/Models';
$fixed = 0;
$errors = 0;

function fixFile(string $filePath): bool
{
    $content = file_get_contents($filePath);
    $originalContent = $content;

    // Pattern 1: Remove orphan closing braces and parentheses after use statements
    // This happens when boot() method is removed but leaves });
    $lines = explode("\n", $content);
    $newLines = [];
    $skip = false;

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        $trimmed = trim($line);

        // Skip lines that are just orphaned closing characters
        if (
            $trimmed === '});' ||
            $trimmed === ');' ||
            ($trimmed === '}' && isset($newLines[count($newLines) - 1]) && !str_contains($newLines[count($newLines) - 1], '{'))
        ) {
            continue;
        }

        // Fix indentation - remove excessive indentation before protected/public/private
        if (preg_match('/^\s{8,}(protected|public|private)\s+/', $line)) {
            $line = preg_replace('/^\s{8,}/', '    ', $line);
        }

        $newLines[] = $line;
    }

    $content = implode("\n", $newLines);

    // Clean up extra blank lines (more than 2 consecutive)
    $content = preg_replace('/\n{4,}/', "\n\n\n", $content);

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
        try {
            if (fixFile($file->getPathname())) {
                echo "✅ Fixed: {$file->getPathname()}\n";
                $fixed++;
            }
        } catch (Exception $e) {
            echo "❌ Error fixing {$file->getPathname()}: {$e->getMessage()}\n";
            $errors++;
        }
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════\n";
echo "📊 Cleanup Statistics\n";
echo "═══════════════════════════════════════════════════\n";
echo "✅ Files fixed: {$fixed}\n";
echo "❌ Errors: {$errors}\n";
echo "═══════════════════════════════════════════════════\n";
