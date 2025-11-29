<?php

// Test media upload manually
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Login as admin
$user = App\Models\Core\User::where('email', 'admin@cmis.test')->first();
if (!$user) {
    die("User not found\n");
}

Auth::login($user);

// Set org context
$orgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

// Create test image
$testImagePath = '/home/cmis-test/public_html/test-results/social-media-images/2R4A9291.JPG';
if (!file_exists($testImagePath)) {
    die("Test image not found at $testImagePath\n");
}

// Create uploaded file
$uploadedFile = new \Illuminate\Http\UploadedFile(
    $testImagePath,
    'test-image.jpg',
    'image/jpeg',
    null,
    true
);

// Create request
$request = \Illuminate\Http\Request::create(
    "/orgs/$orgId/social/media/upload",
    'POST',
    ['type' => 'image'],
    [],
    ['file' => $uploadedFile]
);

// Call controller
$controller = new App\Http\Controllers\Social\MediaLibraryController();

try {
    $response = $controller->upload($request, $orgId);
    echo "Response: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
