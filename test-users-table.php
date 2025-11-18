<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

try {
    // Test 1: Get columns from information_schema
    echo "Test 1: Columns from information_schema:\n";
    $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'users' ORDER BY ordinal_position");
    foreach ($columns as $col) {
        echo "  - " . $col->column_name . "\n";
    }

    // Test 2: Try a simple select
    echo "\nTest 2: Simple SELECT:\n";
    $result = DB::select("SELECT user_id FROM cmis.users LIMIT 1");
    var_dump($result);

    // Test 3: Try to get PDO metadata
    echo "\nTest 3: PDO metadata:\n";
    $pdo = DB::connection()->getPdo();
    $stmt = $pdo->prepare("SELECT * FROM cmis.users WHERE 1=0");
    $stmt->execute();

    for ($i = 0; $i < $stmt->columnCount(); $i++) {
        $meta = $stmt->getColumnMeta($i);
        echo "  Column $i: " . $meta['name'] . "\n";
    }

    // Test 4: Try a simple insert
    echo "\nTest 4: Simple INSERT:\n";
    $pdo->exec("TRUNCATE TABLE cmis.users CASCADE");
    $sql = "INSERT INTO cmis.users (user_id, name, email, password, created_at, updated_at) VALUES (gen_random_uuid(), 'Test', 'test@test.com', 'hash', NOW(), NOW())";
    $result = $pdo->exec($sql);
    echo "  Insert result: $result\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}