<?php

/**
 * Phase 7 Step 2: Refactor Controller Response Patterns
 *
 * This script automatically refactors common response()->json() patterns
 * to use ApiResponse trait methods.
 */

$controllersDir = __DIR__ . '/../app/Http/Controllers';
$refactored = 0;
$totalReplacements = 0;
$errors = [];

function findControllersWithApiResponse(string $dir): array
{
    $controllers = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getPathname();
            if (strpos($path, '/Concerns/') === false) {
                $content = file_get_contents($path);
                // Only process files with ApiResponse trait
                if (strpos($content, 'use ApiResponse;') !== false) {
                    $controllers[] = $path;
                }
            }
        }
    }

    return $controllers;
}

function refactorResponses(string $filePath): int
{
    global $refactored, $totalReplacements, $errors;

    $content = file_get_contents($filePath);
    $originalContent = $content;
    $replacements = 0;

    // Pattern 1: Success responses with 'success' => true
    // response()->json(['success' => true, 'data' => $data, 'message' => '...'], 200)
    // → $this->success($data, '...')
    $pattern1 = '/return\s+response\(\)->json\(\s*\[\s*[\'"]success[\'"]\s*=>\s*true\s*,\s*[\'"]message[\'"]\s*=>\s*([\'"][^"\']+[\'"])\s*,\s*[\'"]data[\'"]\s*=>\s*([^,\]]+)\s*\]\s*,\s*200\s*\)/';
    if (preg_match_all($pattern1, $content)) {
        $content = preg_replace_callback($pattern1, function($matches) {
            return "return \$this->success({$matches[2]}, {$matches[1]})";
        }, $content, -1, $count);
        $replacements += $count;
    }

    // Pattern 2: Success responses without explicit code
    // response()->json(['success' => true, 'data' => $data])
    // → $this->success($data)
    $pattern2 = '/return\s+response\(\)->json\(\s*\[\s*[\'"]success[\'"]\s*=>\s*true\s*,\s*[\'"]data[\'"]\s*=>\s*([^,\]]+)\s*\]\s*\)/';
    if (preg_match_all($pattern2, $content)) {
        $content = preg_replace_callback($pattern2, function($matches) {
            return "return \$this->success({$matches[1]})";
        }, $content, -1, $count);
        $replacements += $count;
    }

    // Pattern 3: Created responses (201)
    // response()->json(['success' => true, 'data' => $data, 'message' => '...'], 201)
    // → $this->created($data, '...')
    $pattern3 = '/return\s+response\(\)->json\(\s*\[\s*[\'"]success[\'"]\s*=>\s*true\s*,\s*[\'"]message[\'"]\s*=>\s*([\'"][^"\']+[\'"])\s*,\s*[\'"]data[\'"]\s*=>\s*([^,\]]+)\s*\]\s*,\s*201\s*\)/';
    if (preg_match_all($pattern3, $content)) {
        $content = preg_replace_callback($pattern3, function($matches) {
            return "return \$this->created({$matches[2]}, {$matches[1]})";
        }, $content, -1, $count);
        $replacements += $count;
    }

    // Pattern 4: Error responses with 'success' => false
    // response()->json(['success' => false, 'message' => '...'], 400)
    // → $this->error('...', 400)
    $pattern4 = '/return\s+response\(\)->json\(\s*\[\s*[\'"]success[\'"]\s*=>\s*false\s*,\s*[\'"]message[\'"]\s*=>\s*([\'"][^"\']+[\'"])\s*\]\s*,\s*(\d+)\s*\)/';
    if (preg_match_all($pattern4, $content)) {
        $content = preg_replace_callback($pattern4, function($matches) {
            return "return \$this->error({$matches[1]}, {$matches[2]})";
        }, $content, -1, $count);
        $replacements += $count;
    }

    // Pattern 5: 404 Not Found responses
    // response()->json(['success' => false, 'message' => '...'], 404)
    // → $this->notFound('...')
    $pattern5 = '/return\s+response\(\)->json\(\s*\[\s*[\'"]success[\'"]\s*=>\s*false\s*,\s*[\'"]message[\'"]\s*=>\s*([\'"][^"\']+[\'"])\s*\]\s*,\s*404\s*\)/';
    if (preg_match_all($pattern5, $content)) {
        $content = preg_replace_callback($pattern5, function($matches) {
            return "return \$this->notFound({$matches[1]})";
        }, $content, -1, $count);
        $replacements += $count;
    }

    // Pattern 6: 422 Validation errors
    // response()->json(['success' => false, 'message' => '...', 'errors' => $errors], 422)
    // → $this->validationError($errors, '...')
    $pattern6 = '/return\s+response\(\)->json\(\s*\[\s*[\'"]success[\'"]\s*=>\s*false\s*,\s*[\'"]message[\'"]\s*=>\s*([\'"][^"\']+[\'"])\s*,\s*[\'"]errors[\'"]\s*=>\s*([^,\]]+)\s*\]\s*,\s*422\s*\)/';
    if (preg_match_all($pattern6, $content)) {
        $content = preg_replace_callback($pattern6, function($matches) {
            return "return \$this->validationError({$matches[2]}, {$matches[1]})";
        }, $content, -1, $count);
        $replacements += $count;
    }

    // Pattern 7: 500 Server errors
    // response()->json(['success' => false, 'message' => '...'], 500)
    // → $this->serverError('...')
    $pattern7 = '/return\s+response\(\)->json\(\s*\[\s*[\'"]success[\'"]\s*=>\s*false\s*,\s*[\'"]message[\'"]\s*=>\s*([\'"][^"\']+[\'"])\s*\]\s*,\s*500\s*\)/';
    if (preg_match_all($pattern7, $content)) {
        $content = preg_replace_callback($pattern7, function($matches) {
            return "return \$this->serverError({$matches[1]})";
        }, $content, -1, $count);
        $replacements += $count;
    }

    // Pattern 8: Simple success with data only
    // response()->json($data, 200)
    // → $this->success($data)
    $pattern8 = '/return\s+response\(\)->json\(\s*(\$[a-zA-Z_][a-zA-Z0-9_]*)\s*,\s*200\s*\)/';
    if (preg_match_all($pattern8, $content)) {
        $content = preg_replace_callback($pattern8, function($matches) {
            return "return \$this->success({$matches[1]})";
        }, $content, -1, $count);
        $replacements += $count;
    }

    // Pattern 9: Simple success with data and message
    // response()->json(['data' => $data, 'message' => '...'])
    // → $this->success($data, '...')
    $pattern9 = '/return\s+response\(\)->json\(\s*\[\s*[\'"]data[\'"]\s*=>\s*([^,\]]+)\s*,\s*[\'"]message[\'"]\s*=>\s*([\'"][^"\']+[\'"])\s*\]\s*\)/';
    if (preg_match_all($pattern9, $content)) {
        $content = preg_replace_callback($pattern9, function($matches) {
            return "return \$this->success({$matches[1]}, {$matches[2]})";
        }, $content, -1, $count);
        $replacements += $count;
    }

    // Only write if changes were made
    if ($content !== $originalContent && $replacements > 0) {
        if (file_put_contents($filePath, $content) !== false) {
            $refactored++;
            $totalReplacements += $replacements;
            echo "✅ Refactored " . basename($filePath) . " ({$replacements} replacements)\n";
            return $replacements;
        } else {
            $errors[] = $filePath;
            echo "❌ Failed to write: " . basename($filePath) . "\n";
            return 0;
        }
    }

    return 0;
}

// Main execution
echo "🚀 Phase 7 Step 2: Refactoring Controller Response Patterns\n";
echo "===========================================================\n\n";

$controllers = findControllersWithApiResponse($controllersDir);
$total = count($controllers);

echo "Found {$total} controllers with ApiResponse trait\n\n";

foreach ($controllers as $controller) {
    refactorResponses($controller);
}

echo "\n===========================================================\n";
echo "✅ Refactored: {$refactored} controllers\n";
echo "📝 Total replacements: {$totalReplacements}\n";
echo "❌ Errors: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\nFailed files:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

echo "\nPhase 7 Step 2 Complete!\n";
echo "Estimated lines saved: ~" . ($totalReplacements * 3) . " lines\n";
