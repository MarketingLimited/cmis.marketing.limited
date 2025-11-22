<?php

/**
 * Phase 7: Apply ApiResponse Trait to All Controllers
 *
 * This script automatically adds the ApiResponse trait to all controllers
 * that don't already use it.
 */

$controllersDir = __DIR__ . '/../app/Http/Controllers';
$traitUse = 'use App\Http\Controllers\Concerns\ApiResponse;';
$applied = 0;
$skipped = 0;
$errors = [];

function findControllers(string $dir): array
{
    $controllers = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getPathname();
            // Skip the Concerns directory
            if (strpos($path, '/Concerns/') === false) {
                $controllers[] = $path;
            }
        }
    }

    return $controllers;
}

function applyApiResponseTrait(string $filePath): bool
{
    global $traitUse, $applied, $skipped, $errors;

    $content = file_get_contents($filePath);

    // Skip if not a Controller class
    if (!preg_match('/class\s+\w+Controller\s+extends/', $content)) {
        $skipped++;
        return false;
    }

    // Skip if already uses ApiResponse
    if (strpos($content, 'use ApiResponse;') !== false) {
        $skipped++;
        echo "⏭️  Skipped (already has trait): " . basename($filePath) . "\n";
        return false;
    }

    // Skip if doesn't have response()->json (not an API controller)
    if (strpos($content, 'response()->json') === false &&
        strpos($content, '->json([') === false) {
        $skipped++;
        return false;
    }

    // Add import statement after namespace declaration
    if (!preg_match('/use App\\\\Http\\\\Controllers\\\\Concerns\\\\ApiResponse;/', $content)) {
        // Find the last 'use' statement before the class declaration
        if (preg_match('/(namespace [^;]+;)(.*?)(class\s+\w+Controller)/s', $content, $matches)) {
            $namespaceDecl = $matches[1];
            $useStatements = $matches[2];
            $classDecl = $matches[3];

            // Check if there are existing use statements
            if (preg_match('/use\s+/', $useStatements)) {
                // Add after existing use statements
                $pattern = '/(use\s+[^;]+;)(\s*)(class\s+\w+Controller)/s';
                if (preg_match($pattern, $content)) {
                    $content = preg_replace(
                        $pattern,
                        "$1\n$traitUse$2$3",
                        $content,
                        1
                    );
                }
            } else {
                // No existing use statements, add after namespace
                $content = str_replace(
                    $namespaceDecl,
                    $namespaceDecl . "\n\n" . $traitUse,
                    $content
                );
            }
        }
    }

    // Add trait usage in class body
    if (preg_match('/(class\s+\w+Controller\s+extends\s+\w+\s*\{)(\s*)/', $content, $matches)) {
        $classOpening = $matches[1];
        $whitespace = $matches[2];

        // Check if there are existing traits
        $afterClassPattern = '/(class\s+\w+Controller\s+extends\s+\w+\s*\{)(\s*)(use\s+[^;]+;)?/s';

        if (preg_match($afterClassPattern, $content, $traitMatches)) {
            if (!empty($traitMatches[3]) && strpos($traitMatches[3], 'use ') === 0) {
                // Has existing traits, add ApiResponse to them
                $existingTraits = $traitMatches[3];
                if (strpos($existingTraits, 'ApiResponse') === false) {
                    // Add ApiResponse to existing trait list
                    $newTraits = rtrim($existingTraits, ';') . ', ApiResponse;';
                    $content = str_replace($existingTraits, $newTraits, $content);
                }
            } else {
                // No existing traits, add new use statement
                $content = str_replace(
                    $classOpening,
                    $classOpening . "\n    use ApiResponse;\n",
                    $content
                );
            }
        }
    }

    // Write back to file
    if (file_put_contents($filePath, $content) !== false) {
        $applied++;
        echo "✅ Applied trait to: " . basename($filePath) . "\n";
        return true;
    } else {
        $errors[] = $filePath;
        echo "❌ Failed to write: " . basename($filePath) . "\n";
        return false;
    }
}

// Main execution
echo "🚀 Phase 7: Applying ApiResponse Trait to Controllers\n";
echo "====================================================\n\n";

$controllers = findControllers($controllersDir);
$total = count($controllers);

echo "Found {$total} PHP files in Controllers directory\n\n";

foreach ($controllers as $controller) {
    applyApiResponseTrait($controller);
}

echo "\n====================================================\n";
echo "✅ Applied trait to: {$applied} controllers\n";
echo "⏭️  Skipped: {$skipped} files\n";
echo "❌ Errors: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\nFailed files:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

echo "\nPhase 7 Step 1 Complete!\n";
