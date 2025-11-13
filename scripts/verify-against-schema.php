<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 COMPREHENSIVE SCHEMA VERIFICATION\n";
echo "================================================================================\n\n";

// Parse schema.sql to extract table structures
$schemaFile = __DIR__ . '/../database/schema.sql';
$schemaContent = file_get_contents($schemaFile);

// Extract key table definitions
$tables = [
    'users' => [],
    'orgs' => [],
    'campaigns' => [],
    'user_orgs' => [],
    'ad_entities' => [],
    'roles' => [],
    'permissions' => [],
];

foreach ($tables as $tableName => &$tableInfo) {
    // Extract CREATE TABLE statement
    if (preg_match("/CREATE TABLE cmis\\.{$tableName}\s*\((.*?)\);/s", $schemaContent, $matches)) {
        $createStatement = $matches[1];

        // Extract columns
        preg_match_all("/(\w+)\s+(\w+|\w+\.\w+)/", $createStatement, $columnMatches);
        $tableInfo['columns'] = $columnMatches[1];

        // Find primary key
        if (preg_match("/(\w+)\s+uuid.*?DEFAULT.*?NOT NULL,?$/m", $createStatement, $pkMatch)) {
            $tableInfo['primary_key'] = $pkMatch[1];
        }

        // Check for deleted_at
        $tableInfo['has_soft_deletes'] = strpos($createStatement, 'deleted_at') !== false;
    }
}

echo "📊 Schema Analysis:\n\n";
foreach ($tables as $tableName => $tableInfo) {
    if (!empty($tableInfo['columns'])) {
        echo "Table: cmis.$tableName\n";
        echo "  Primary Key: " . ($tableInfo['primary_key'] ?? 'N/A') . "\n";
        echo "  Soft Deletes: " . ($tableInfo['has_soft_deletes'] ? 'YES' : 'NO') . "\n";
        echo "  Columns: " . implode(', ', array_slice($tableInfo['columns'], 0, 10)) . "\n";
        echo "\n";
    }
}

// Check models
echo "\n📋 Checking Models Against Schema...\n\n";

$modelChecks = [
    'App\Models\User' => 'users',
    'App\Models\Core\Org' => 'orgs',
    'App\Models\Campaign' => 'campaigns',
    'App\Models\Core\UserOrg' => 'user_orgs',
    'App\Models\AdEntity' => 'ad_entities',
];

$issues = [];

foreach ($modelChecks as $modelClass => $tableName) {
    try {
        if (!class_exists($modelClass)) {
            echo "  ⚠️  $modelClass: Class not found\n";
            continue;
        }

        $model = new $modelClass();
        $modelPrimaryKey = $model->getKeyName();
        $schemaPrimaryKey = $tables[$tableName]['primary_key'] ?? null;

        $modelHasSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass));
        $schemaHasSoftDeletes = $tables[$tableName]['has_soft_deletes'] ?? false;

        echo "  $modelClass:\n";

        // Check primary key
        if ($modelPrimaryKey !== $schemaPrimaryKey) {
            $issue = "    ❌ Primary key mismatch: Model uses '$modelPrimaryKey', Schema has '$schemaPrimaryKey'";
            echo "$issue\n";
            $issues[] = $issue;
        } else {
            echo "    ✅ Primary key: $modelPrimaryKey\n";
        }

        // Check soft deletes
        if ($modelHasSoftDeletes !== $schemaHasSoftDeletes) {
            $modelStatus = $modelHasSoftDeletes ? 'HAS' : 'MISSING';
            $schemaStatus = $schemaHasSoftDeletes ? 'REQUIRED' : 'NOT REQUIRED';
            $issue = "    ❌ SoftDeletes mismatch: Model $modelStatus SoftDeletes, Schema $schemaStatus";
            echo "$issue\n";
            $issues[] = $issue;
        } else {
            $status = $modelHasSoftDeletes ? 'Enabled' : 'Disabled';
            echo "    ✅ SoftDeletes: $status\n";
        }

        echo "\n";

    } catch (\Exception $e) {
        echo "  ❌ $modelClass: " . $e->getMessage() . "\n\n";
    }
}

// Check controllers for field references
echo "\n📋 Checking Controllers for Field Name Usage...\n\n";

$controllerFiles = glob(__DIR__ . '/../app/Http/Controllers/**/*.php');
$fieldIssues = [];

$wrongFields = [
    'user\.id(?!entity)' => 'Should be: user.user_id',
    'org\.id(?!entity)' => 'Should be: org.org_id',
    'campaign\.id(?!entity)' => 'Should be: campaign.campaign_id',
];

foreach ($controllerFiles as $file) {
    $content = file_get_contents($file);
    $shortPath = str_replace(__DIR__ . '/../', '', $file);

    foreach ($wrongFields as $pattern => $message) {
        if (preg_match("/$pattern/", $content)) {
            $fieldIssues[] = "$shortPath: $message";
        }
    }
}

if (empty($fieldIssues)) {
    echo "✅ No obvious field name issues in controllers\n";
} else {
    echo "⚠️  Potential field name issues:\n";
    foreach (array_slice($fieldIssues, 0, 10) as $issue) {
        echo "  - $issue\n";
    }
    if (count($fieldIssues) > 10) {
        echo "  ... and " . (count($fieldIssues) - 10) . " more\n";
    }
}

// Summary
echo "\n================================================================================\n";
echo "📊 VERIFICATION SUMMARY\n";
echo "================================================================================\n\n";

if (empty($issues)) {
    echo "✅ ALL MODELS MATCH SCHEMA!\n";
} else {
    echo "❌ FOUND " . count($issues) . " CRITICAL ISSUES:\n\n";
    foreach ($issues as $issue) {
        echo "$issue\n";
    }
    echo "\n⚠️  MODELS NEED TO BE UPDATED TO MATCH SCHEMA.SQL!\n";
}

echo "\n";
