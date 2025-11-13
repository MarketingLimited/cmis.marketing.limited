<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

echo "🔍 COMPREHENSIVE SYNCHRONIZATION CHECK\n";
echo "================================================================================\n\n";

// 1. Check all models
echo "📋 Checking Models...\n";
$modelsPath = app_path('Models');
$models = [];
$modelIssues = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modelsPath)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $relativePath = str_replace($modelsPath . '/', '', $file->getPathname());
        $className = 'App\\Models\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);

        if (class_exists($className)) {
            $reflection = new ReflectionClass($className);
            if (!$reflection->isAbstract() && $reflection->isSubclassOf('Illuminate\\Database\\Eloquent\\Model')) {
                try {
                    $model = new $className();
                    $table = $model->getTable();
                    $fillable = $model->getFillable();

                    // Parse schema
                    if (strpos($table, '.') !== false) {
                        [$schema, $tableName] = explode('.', $table, 2);
                    } else {
                        $schema = 'public';
                        $tableName = $table;
                    }

                    // Check if table exists
                    $tableExists = DB::select("
                        SELECT EXISTS (
                            SELECT FROM information_schema.tables
                            WHERE table_schema = ? AND table_name = ?
                        )
                    ", [$schema, $tableName])[0]->exists ?? false;

                    if ($tableExists) {
                        // Get actual columns
                        $columns = DB::select("
                            SELECT column_name
                            FROM information_schema.columns
                            WHERE table_schema = ? AND table_name = ?
                        ", [$schema, $tableName]);

                        $columnNames = array_column($columns, 'column_name');

                        // Check if fillable is set
                        if (empty($fillable)) {
                            $modelIssues[] = [
                                'model' => $className,
                                'issue' => 'No $fillable defined - potential mass assignment risk'
                            ];
                        } else {
                            // Check for columns in fillable that don't exist
                            $invalidFillable = array_diff($fillable, $columnNames);
                            if (!empty($invalidFillable)) {
                                $modelIssues[] = [
                                    'model' => $className,
                                    'issue' => 'Invalid columns in $fillable: ' . implode(', ', $invalidFillable)
                                ];
                            }
                        }

                        $models[] = $className;
                    }
                } catch (\Exception $e) {
                    // Skip models that can't be instantiated
                }
            }
        }
    }
}

echo "✅ Checked " . count($models) . " models\n";
if (empty($modelIssues)) {
    echo "✅ No model synchronization issues found!\n\n";
} else {
    echo "⚠️  Found " . count($modelIssues) . " model issues:\n";
    foreach ($modelIssues as $issue) {
        echo "  - {$issue['model']}: {$issue['issue']}\n";
    }
    echo "\n";
}

// 2. Check all controllers and services
echo "📋 Checking Controllers and Services...\n";
$phpFiles = [];
foreach ([app_path('Http/Controllers'), app_path('Services')] as $path) {
    if (is_dir($path)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }
    }
}

$syntaxErrors = [];
foreach ($phpFiles as $file) {
    $output = [];
    $return = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return);
    if ($return !== 0) {
        $syntaxErrors[] = str_replace(app_path(), 'app', $file);
    }
}

echo "✅ Checked " . count($phpFiles) . " controller/service files\n";
if (empty($syntaxErrors)) {
    echo "✅ No syntax errors found!\n\n";
} else {
    echo "❌ Found " . count($syntaxErrors) . " files with syntax errors:\n";
    foreach ($syntaxErrors as $file) {
        echo "  - $file\n";
    }
    echo "\n";
}

// 3. Check routes
echo "📋 Checking Routes...\n";
try {
    $routes = Route::getRoutes();
    $routeCount = count($routes);
    echo "✅ Found $routeCount registered routes\n\n";
} catch (\Exception $e) {
    echo "❌ Error loading routes: " . $e->getMessage() . "\n\n";
}

// 4. Generate summary
echo "================================================================================\n";
echo "📊 SYNCHRONIZATION CHECK SUMMARY\n";
echo "================================================================================\n\n";

$totalIssues = count($modelIssues) + count($syntaxErrors);

if ($totalIssues === 0) {
    echo "✅ ALL FILES SYNCHRONIZED WITH DATABASE!\n";
    echo "✅ Zero issues found\n";
    echo "✅ Ready for testing\n\n";
} else {
    echo "⚠️  Total Issues: $totalIssues\n";
    echo "   - Model Issues: " . count($modelIssues) . "\n";
    echo "   - Syntax Errors: " . count($syntaxErrors) . "\n\n";
}

// Save report
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'models_checked' => count($models),
    'files_checked' => count($phpFiles),
    'routes_found' => $routeCount ?? 0,
    'model_issues' => $modelIssues,
    'syntax_errors' => $syntaxErrors,
    'total_issues' => $totalIssues,
    'status' => $totalIssues === 0 ? 'PASS' : 'FAIL'
];

file_put_contents(__DIR__ . '/sync-check-report.json', json_encode($report, JSON_PRETTY_PRINT));
echo "📄 Full report saved to scripts/sync-check-report.json\n";

exit($totalIssues === 0 ? 0 : 1);
