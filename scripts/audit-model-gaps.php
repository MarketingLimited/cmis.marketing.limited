#!/usr/bin/env php
<?php
/**
 * CMIS Model Gap Analysis Script
 * Standalone script to identify missing Laravel models
 */

echo "🔍 CMIS Model Gap Analysis\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Paths
$basePath = dirname(__DIR__);
$schemaPath = $basePath . '/database/schema.sql';
$modelsPath = $basePath . '/app/Models';
$outputPath = $basePath . '/reports/REAL_MODEL_GAPS_AUDIT.md';

// Step 1: Extract tables from schema
echo "📊 Extracting tables from schema.sql...\n";

if (!file_exists($schemaPath)) {
    die("❌ Error: schema.sql not found at {$schemaPath}\n");
}

$schemaContent = file_get_contents($schemaPath);
preg_match_all('/CREATE TABLE cmis\.([a-z_]+)\s*\(/i', $schemaContent, $matches);
$tables = array_unique($matches[1]);
sort($tables);

echo "   ✅ Found " . count($tables) . " tables\n\n";

// Step 2: Scan existing models
echo "📁 Scanning existing Laravel models...\n";

$models = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modelsPath)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $content = file_get_contents($file->getPathname());

    // Extract table name from: protected $table = 'cmis.table_name';
    if (preg_match('/protected\s+\$table\s*=\s*[\'"]cmis\.([a-z_]+)[\'"]/i', $content, $match)) {
        $tableName = $match[1];
        $modelClass = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        $relativePath = str_replace($basePath . '/', '', $file->getPathname());

        $models[$tableName] = [
            'class' => $modelClass,
            'path' => $relativePath,
        ];
    }
}

echo "   ✅ Found " . count($models) . " models with defined tables\n\n";

// Step 3: Identify gaps
echo "🔎 Identifying gaps...\n";

$existingModels = [];
$missingModels = [];

foreach ($tables as $table) {
    if (isset($models[$table])) {
        $existingModels[$table] = $models[$table];
    } else {
        $missingModels[$table] = tableToModelName($table);
    }
}

$missingCount = count($missingModels);
$existingCount = count($existingModels);
$totalCount = count($tables);
$coverage = $totalCount > 0 ? round(($existingCount / $totalCount) * 100, 1) : 0;

echo "   ⚠️  Missing: {$missingCount} models\n";
echo "   ✅ Existing: {$existingCount} models\n";
echo "   📊 Coverage: {$coverage}%\n\n";

// Step 4: Generate report
echo "📝 Generating markdown report...\n";

$timestamp = date('Y-m-d H:i:s');
$report = <<<MARKDOWN
# CMIS Model Gap Analysis Report

**Generated:** {$timestamp}
**Schema File:** `database/schema.sql`
**Models Directory:** `app/Models/`

---

## 📊 Executive Summary

| Metric | Value |
|--------|-------|
| Total Tables | {$totalCount} |
| Models Exist | {$existingCount} |
| Models Missing | {$missingCount} |
| **Coverage** | **{$coverage}%** |

---

## 🎯 Status Assessment

MARKDOWN;

if ($coverage >= 90) {
    $report .= "✅ **Status:** EXCELLENT - Near complete coverage\n\n";
} elseif ($coverage >= 70) {
    $report .= "⚠️ **Status:** GOOD - Most models exist, minor gaps\n\n";
} elseif ($coverage >= 50) {
    $report .= "⚠️ **Status:** MODERATE - Significant gaps exist\n\n";
} else {
    $report .= "🚨 **Status:** CRITICAL - Major model gaps detected\n\n";
}

// Critical Issues
$report .= "### 🚨 Critical Findings\n\n";

$criticalTables = ['permissions_cache', 'session_context', 'audit_log'];
$criticalMissing = [];
foreach ($criticalTables as $ct) {
    if (isset($missingModels[$ct])) {
        $criticalMissing[] = $ct;
    }
}

if (!empty($criticalMissing)) {
    $report .= "The following **CRITICAL** security/core models are missing:\n\n";
    foreach ($criticalMissing as $ct) {
        $report .= "- ⚠️ `{$ct}` → `{$missingModels[$ct]}`\n";
    }
} else {
    $report .= "✅ All critical security models exist.\n";
}

$report .= "\n---\n\n";

// Missing Models Section
$report .= "## ❌ Missing Models ({$missingCount})\n\n";

if ($missingCount > 0) {
    $report .= "The following database tables **lack corresponding Laravel models**:\n\n";
    $report .= "| # | Table Name | Expected Model | Category | Suggested Path |\n";
    $report .= "|---|------------|----------------|----------|----------------|\n";

    $counter = 1;
    foreach ($missingModels as $table => $modelName) {
        $category = categorizeTable($table);
        $path = "app/Models/{$category}/{$modelName}.php";
        $report .= "| {$counter} | `{$table}` | `{$modelName}` | {$category} | `{$path}` |\n";
        $counter++;
    }
} else {
    $report .= "✅ No missing models! All database tables have corresponding models.\n";
}

$report .= "\n---\n\n";

// Existing Models Section
$report .= "## ✅ Existing Models ({$existingCount})\n\n";
$report .= "| # | Table Name | Model Class | File Path |\n";
$report .= "|---|------------|-------------|----------|\n";

$counter = 1;
foreach ($existingModels as $table => $info) {
    $report .= "| {$counter} | `{$table}` | `{$info['class']}` | `{$info['path']}` |\n";
    $counter++;
}

$report .= "\n---\n\n";

// Categorized Missing Models
$report .= "## 📂 Missing Models by Category\n\n";

$categorized = [];
foreach ($missingModels as $table => $model) {
    $category = categorizeTable($table);
    $categorized[$category][] = ['table' => $table, 'model' => $model];
}

ksort($categorized);

foreach ($categorized as $category => $items) {
    $count = count($items);
    $report .= "### {$category} ({$count})\n\n";
    foreach ($items as $item) {
        $report .= "- `{$item['table']}` → `{$item['model']}`\n";
    }
    $report .= "\n";
}

$report .= "---\n\n";

// Priority Recommendations
$report .= "## 🎯 Priority Recommendations\n\n";
$report .= "### 🔴 Critical Priority (Security & Core)\n\n";
$report .= "These models are **essential** for system security and basic functionality:\n\n";

$criticalPriority = ['permissions_cache', 'session_context', 'audit_log', 'user_activities'];
foreach ($criticalPriority as $table) {
    if (isset($missingModels[$table])) {
        $report .= "- ⚠️ **`{$table}`** → `{$missingModels[$table]}`\n";
    }
}

$report .= "\n### 🟡 High Priority (Business Logic)\n\n";
$report .= "These models are required for core business features:\n\n";

$businessLogicCategories = ['Campaign', 'Creative', 'Knowledge', 'Context'];
$highPriority = [];
foreach ($missingModels as $table => $model) {
    $category = categorizeTable($table);
    if (in_array($category, $businessLogicCategories)) {
        $highPriority[$table] = $model;
    }
}

$counter = 0;
foreach ($highPriority as $table => $model) {
    if ($counter++ >= 15) break;
    $report .= "- 🔸 **`{$table}`** → `{$model}`\n";
}

$report .= "\n---\n\n";

// Conclusion
$report .= "## 🎬 Conclusion & Next Steps\n\n";

if ($coverage < 50) {
    $report .= "❌ **The project is in a CRITICAL state.** Over half of the database tables lack models.\n\n";
    $report .= "**Recommended Actions:**\n";
    $report .= "1. 🚨 Immediately create critical security models\n";
    $report .= "2. 🔥 Pause new feature development\n";
    $report .= "3. 📋 Create a systematic model generation plan\n";
    $report .= "4. ✅ Implement testing for all new models\n";
} elseif ($coverage < 70) {
    $report .= "⚠️ **The project has significant gaps** that need addressing.\n\n";
    $report .= "**Recommended Actions:**\n";
    $report .= "1. Focus on high-priority business logic models\n";
    $report .= "2. Implement systematic testing\n";
    $report .= "3. Document model-table relationships\n";
} else {
    $report .= "✅ **The project has good model coverage.** Focus on closing remaining gaps.\n\n";
    $report .= "**Recommended Actions:**\n";
    $report .= "1. Create remaining models systematically\n";
    $report .= "2. Ensure all models have proper tests\n";
    $report .= "3. Keep this audit up to date\n";
}

$report .= "\n---\n\n";
$report .= "*This report was generated automatically by `scripts/audit-model-gaps.php`*\n";

// Save report
@mkdir(dirname($outputPath), 0755, true);
file_put_contents($outputPath, $report);

echo "   ✅ Report saved to: reports/REAL_MODEL_GAPS_AUDIT.md\n\n";

echo "✅ Audit complete!\n";
echo "=" . str_repeat("=", 50) . "\n";

// Helper Functions
function tableToModelName(string $table): string
{
    // Convert snake_case to PascalCase
    $words = explode('_', $table);
    $words = array_map('ucfirst', $words);
    $modelName = implode('', $words);

    // Singularize (basic implementation)
    if (substr($modelName, -3) === 'ies') {
        $modelName = substr($modelName, 0, -3) . 'y';
    } elseif (substr($modelName, -1) === 's' && substr($modelName, -2) !== 'ss') {
        $modelName = substr($modelName, 0, -1);
    }

    return $modelName;
}

function categorizeTable(string $table): string
{
    $categories = [
        'Security' => ['permission', 'role', 'session', 'audit'],
        'Core' => ['org', 'user', 'setting'],
        'Context' => ['context', 'field_definition', 'field_value'],
        'Campaign' => ['campaign'],
        'Creative' => ['creative', 'content', 'copy', 'brief', 'asset'],
        'AdPlatform' => ['ad_', 'meta_', 'tiktok_', 'twitter_'],
        'Social' => ['social_', 'post_', 'channel_', 'message'],
        'Knowledge' => ['knowledge_', 'embedding', 'semantic'],
        'AI' => ['ai_', 'cognitive', 'model_'],
        'Analytics' => ['metric', 'kpi', 'analytic'],
        'Operations' => ['ops_', 'etl_', 'sync_', 'log'],
        'Integration' => ['integration', 'webhook', 'oauth'],
    ];

    foreach ($categories as $category => $patterns) {
        foreach ($patterns as $pattern) {
            if (stripos($table, $pattern) !== false) {
                return $category;
            }
        }
    }

    return 'Other';
}
