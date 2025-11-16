<?php
/**
 * Script to add HasUuids trait to models that are missing UUID auto-generation
 * Only processes models with $incrementing = false and $keyType = 'string'
 * Skips view models (V*) and models that already have UUID generation
 */

$modelsPath = __DIR__ . '/../app/Models';

// Models to skip (views, already have UUID generation, or special cases)
$skipPatterns = [
    '/^V[A-Z]/',  // View models start with V
    'User.php',   // Already has UUID boot()
    'Campaign.php', // Already has UUID boot()
    'Org.php',    // Already has UUID boot()
    'ContentPlan.php', // Already has UUID boot()
];

function processModel($filePath) {
    global $skipPatterns;

    $fileName = basename($filePath);

    // Skip certain patterns
    foreach ($skipPatterns as $pattern) {
        if (is_string($pattern) && $fileName === $pattern) {
            return "SKIPPED (in skip list): $filePath";
        }
        if (strpos($pattern, '/') === 0 && preg_match($pattern, $fileName)) {
            return "SKIPPED (view model): $filePath";
        }
    }

    $content = file_get_contents($filePath);

    // Check if model has both $incrementing = false and $keyType = 'string'
    if (!preg_match('/\$incrementing\s*=\s*false/', $content)) {
        return "SKIPPED (auto-increment): $filePath";
    }

    if (!preg_match('/\$keyType\s*=\s*[\'"]string[\'"]/', $content)) {
        return "SKIPPED (not string key): $filePath";
    }

    // Check if already has HasUuids trait
    if (preg_match('/use\s+.*HasUuids/', $content)) {
        return "SKIPPED (already has HasUuids): $filePath";
    }

    // Check if already has boot() with UUID generation
    if (preg_match('/static::creating\s*\(\s*function.*Str::uuid\(\)/', $content, $matches)) {
        return "SKIPPED (has boot UUID): $filePath";
    }

    // Add HasUuids trait
    $modified = false;

    // Add import if not exists
    if (!preg_match('/use\s+Illuminate\\\\Database\\\\Eloquent\\\\Concerns\\\\HasUuids/', $content)) {
        // Find the last use statement before the class definition
        $content = preg_replace(
            '/(namespace\s+[^;]+;\s*)(use\s+[^;]+;\s*)+/',
            "$0use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;\n",
            $content,
            1,
            $count
        );
        if ($count === 0) {
            // If no use statements found, add after namespace
            $content = preg_replace(
                '/(namespace\s+[^;]+;)/',
                "$1\n\nuse Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;",
                $content,
                1
            );
        }
        $modified = true;
    }

    // Add trait usage in class if not exists
    if (!preg_match('/class\s+\w+\s+extends\s+Model[^{]*\{[^}]*use\s+[^;]*HasUuids/', $content)) {
        // Find trait usage and add HasUuids
        if (preg_match('/(class\s+\w+\s+extends\s+Model[^{]*\{[^}]*)(use\s+)([^;]+)(;)/', $content, $matches)) {
            // Already has use statement, add HasUuids to it
            $existingTraits = $matches[3];
            if (strpos($existingTraits, 'HasUuids') === false) {
                $newTraits = $existingTraits . ', HasUuids';
                $content = str_replace($matches[0], $matches[1] . $matches[2] . $newTraits . $matches[4], $content);
                $modified = true;
            }
        } else {
            // No trait usage, add one after class opening
            $content = preg_replace(
                '/(class\s+\w+\s+extends\s+Model[^{]*\{)/',
                "$1\n    use HasUuids;",
                $content,
                1,
                $count
            );
            if ($count > 0) {
                $modified = true;
            }
        }
    }

    if ($modified) {
        file_put_contents($filePath, $content);
        return "UPDATED: $filePath";
    }

    return "NO CHANGES: $filePath";
}

function scanDirectory($dir) {
    $results = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $results[] = processModel($file->getPathname());
        }
    }

    return $results;
}

echo "Processing models in: $modelsPath\n";
echo str_repeat('=', 60) . "\n";

$results = scanDirectory($modelsPath);

$stats = [
    'UPDATED' => 0,
    'SKIPPED' => 0,
    'NO CHANGES' => 0,
];

foreach ($results as $result) {
    if (strpos($result, 'UPDATED:') === 0) {
        $stats['UPDATED']++;
        echo $result . "\n";
    } elseif (strpos($result, 'SKIPPED') === 0) {
        $stats['SKIPPED']++;
    } else {
        $stats['NO CHANGES']++;
    }
}

echo str_repeat('=', 60) . "\n";
echo "Summary:\n";
echo "  Updated: {$stats['UPDATED']}\n";
echo "  Skipped: {$stats['SKIPPED']}\n";
echo "  No Changes: {$stats['NO CHANGES']}\n";
