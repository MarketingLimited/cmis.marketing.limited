#!/usr/bin/env php
<?php
/**
 * API Response Refactoring Script
 *
 * Automatically refactors controllers to use ApiResponse trait methods
 * instead of manual response()->json() calls.
 */

if ($argc < 2) {
    echo "Usage: php refactor_api_responses.php <controller_file>\n";
    exit(1);
}

$file = $argv[1];

if (!file_exists($file)) {
    echo "Error: File not found: $file\n";
    exit(1);
}

$content = file_get_contents($file);
$originalContent = $content;
$changes = [];

// Step 1: Ensure proper imports
if (!str_contains($content, 'use App\Http\Controllers\Concerns\ApiResponse;')) {
    // Find the namespace line and add import after other use statements
    $content = preg_replace(
        '/(namespace [^;]+;\n\nuse [^\n]+;)/s',
        "$1\nuse App\\Http\\Controllers\\Concerns\\ApiResponse;",
        $content,
        1,
        $count
    );
    if ($count > 0) {
        $changes[] = "Added ApiResponse import";
    }
}

if (!str_contains($content, 'use Illuminate\Http\JsonResponse;')) {
    $content = preg_replace(
        '/(use [^\n]+Request;)/s',
        "$1\nuse Illuminate\\Http\\JsonResponse;",
        $content,
        1,
        $count
    );
    if ($count > 0) {
        $changes[] = "Added JsonResponse import";
    }
}

// Step 2: Replace return types in docblocks and method signatures
$content = preg_replace(
    '/@return\s+\\\\Illuminate\\\\Http\\\\JsonResponse/',
    '@return JsonResponse',
    $content,
    -1,
    $count
);
if ($count > 0) {
    $changes[] = "Fixed $count return type docblocks";
}

// Add return type declarations where missing
$content = preg_replace(
    '/(public\s+function\s+\w+\([^)]*\))(\s*\n\s*\{)/',
    '$1: JsonResponse$2',
    $content,
    -1,
    $count
);
if ($count > 0) {
    $changes[] = "Added $count return type declarations";
}

// Step 3: Convert common response patterns
$patterns = [
    // Pattern: return response()->json(['success' => true, ...], 201) → return $this->created(...)
    [
        'pattern' => '/return\s+response\(\)->json\(\[\s*["\']success["\']\s*=>\s*true,\s*(.+?)\],\s*201\);/s',
        'replacement' => 'return $this->created([$1], \'Resource created successfully\');',
        'name' => '201 Created responses'
    ],

    // Pattern: return response()->json(['success' => true, 'message' => ...], 200) → return $this->success(...)
    [
        'pattern' => '/return\s+response\(\)->json\(\[\s*["\']success["\']\s*=>\s*true,\s*["\']message["\']\s*=>\s*(["\'][^"\']+["\'])\s*\],\s*200\);/s',
        'replacement' => 'return $this->deleted($1);',
        'name' => 'Delete success responses'
    ],

    // Pattern: return response()->json(['success' => false, 'error' => ...], 500) → return $this->serverError(...)
    [
        'pattern' => '/return\s+response\(\)->json\(\[\s*["\']success["\']\s*=>\s*false,\s*["\']error["\']\s*=>\s*([^,\]]+).*?\],\s*500\);/s',
        'replacement' => 'return $this->serverError($1);',
        'name' => '500 Error responses'
    ],

    // Pattern: return response()->json(['error' => ...], 404) → return $this->notFound(...)
    [
        'pattern' => '/return\s+response\(\)->json\(\[\s*["\']error["\']\s*=>\s*(["\'][^"\']+["\'])\s*\],\s*404\);/s',
        'replacement' => 'return $this->notFound($1);',
        'name' => '404 Not Found responses'
    ],

    // Pattern: return response()->json(['error' => ..., 'errors' => ...], 422) → return $this->validationError(...)
    [
        'pattern' => '/return\s+response\(\)->json\(\[\s*["\']error["\']\s*=>\s*([^,]+),\s*["\']errors["\']\s*=>\s*([^\]]+)\],\s*422\);/s',
        'replacement' => 'return $this->validationError($2, $1);',
        'name' => '422 Validation Error responses'
    ],

    // Pattern: return response()->json($data) → return $this->success($data, 'Success')
    [
        'pattern' => '/return\s+response\(\)->json\((\$\w+)\);/s',
        'replacement' => 'return $this->success($1, \'Data retrieved successfully\');',
        'name' => 'Simple data responses'
    ],
];

foreach ($patterns as $pattern) {
    $content = preg_replace(
        $pattern['pattern'],
        $pattern['replacement'],
        $content,
        -1,
        $count
    );
    if ($count > 0) {
        $changes[] = "Converted $count {$pattern['name']}";
    }
}

// Only write if changes were made
if ($content !== $originalContent) {
    file_put_contents($file, $content);
    echo "✓ Refactored: " . basename($file) . "\n";
    foreach ($changes as $change) {
        echo "  - $change\n";
    }
    exit(0);
} else {
    echo "- No changes needed: " . basename($file) . "\n";
    exit(0);
}
