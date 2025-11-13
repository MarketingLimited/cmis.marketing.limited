<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AuditModelGaps extends Command
{
    protected $signature = 'audit:model-gaps
                          {--output=reports/model-gaps-audit.md : Output file path}
                          {--format=markdown : Output format (markdown|json)}';

    protected $description = 'Audit database tables and identify missing Laravel models';

    protected array $tables = [];
    protected array $models = [];
    protected array $missingModels = [];
    protected array $existingModels = [];

    public function handle(): int
    {
        $this->info('🔍 Starting Model Gap Analysis...');
        $this->newLine();

        // Step 1: Extract tables from schema.sql
        $this->extractTablesFromSchema();

        // Step 2: Scan existing models
        $this->scanExistingModels();

        // Step 3: Compare and identify gaps
        $this->identifyGaps();

        // Step 4: Generate report
        $outputPath = $this->option('output');
        $format = $this->option('format');

        if ($format === 'json') {
            $this->generateJsonReport($outputPath);
        } else {
            $this->generateMarkdownReport($outputPath);
        }

        $this->newLine();
        $this->info("✅ Audit complete! Report saved to: {$outputPath}");

        return Command::SUCCESS;
    }

    protected function extractTablesFromSchema(): void
    {
        $this->info('📊 Extracting tables from schema.sql...');

        $schemaPath = database_path('schema.sql');
        if (!File::exists($schemaPath)) {
            $this->error('schema.sql not found!');
            return;
        }

        $content = File::get($schemaPath);

        // Match: CREATE TABLE cmis.table_name (
        preg_match_all(
            '/CREATE TABLE cmis\.([a-z_]+)\s*\(/i',
            $content,
            $matches
        );

        if (!empty($matches[1])) {
            $this->tables = array_unique($matches[1]);
            sort($this->tables);
            $this->info("   Found " . count($this->tables) . " tables in schema");
        }
    }

    protected function scanExistingModels(): void
    {
        $this->info('📁 Scanning existing Laravel models...');

        $modelsPath = app_path('Models');
        $files = File::allFiles($modelsPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = File::get($file->getPathname());

            // Extract table name from: protected $table = 'cmis.table_name';
            if (preg_match('/protected\s+\$table\s*=\s*[\'"]cmis\.([a-z_]+)[\'"]/i', $content, $match)) {
                $tableName = $match[1];
                $modelClass = $file->getFilenameWithoutExtension();

                $this->models[$tableName] = [
                    'class' => $modelClass,
                    'path' => str_replace(app_path(), 'app', $file->getPathname()),
                ];
            }
        }

        $this->info("   Found " . count($this->models) . " models with defined tables");
    }

    protected function identifyGaps(): void
    {
        $this->info('🔎 Identifying gaps...');

        foreach ($this->tables as $table) {
            if (isset($this->models[$table])) {
                $this->existingModels[$table] = $this->models[$table];
            } else {
                // Generate expected model name
                $modelName = $this->tableToModelName($table);
                $this->missingModels[$table] = $modelName;
            }
        }

        $missingCount = count($this->missingModels);
        $existingCount = count($this->existingModels);
        $totalCount = count($this->tables);
        $coverage = $totalCount > 0 ? round(($existingCount / $totalCount) * 100, 1) : 0;

        $this->warn("   Missing: {$missingCount} models");
        $this->info("   Existing: {$existingCount} models");
        $this->info("   Coverage: {$coverage}%");
    }

    protected function tableToModelName(string $table): string
    {
        // Convert snake_case to PascalCase
        // Example: ad_campaigns -> AdCampaign
        return Str::studly(Str::singular($table));
    }

    protected function generateMarkdownReport(string $outputPath): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $totalTables = count($this->tables);
        $existingCount = count($this->existingModels);
        $missingCount = count($this->missingModels);
        $coverage = $totalTables > 0 ? round(($existingCount / $totalTables) * 100, 1) : 0;

        $report = <<<MARKDOWN
# CMIS Model Gap Analysis Report

**Generated:** {$timestamp}
**Schema File:** `database/schema.sql`

---

## Executive Summary

| Metric | Value |
|--------|-------|
| Total Tables | {$totalTables} |
| Models Exist | {$existingCount} |
| Models Missing | {$missingCount} |
| **Coverage** | **{$coverage}%** |

---

## Status Assessment

MARKDOWN;

        if ($coverage >= 90) {
            $report .= "✅ **Status:** EXCELLENT - Near complete coverage\n\n";
        } elseif ($coverage >= 70) {
            $report .= "⚠️ **Status:** GOOD - Most models exist, minor gaps\n\n";
        } elseif ($coverage >= 50) {
            $report .= "⚠️ **Status:** MODERATE - Significant gaps exist\n\n";
        } else {
            $report .= "🚨 **Status:** CRITICAL - Major model gaps\n\n";
        }

        // Missing Models Section
        $report .= "---\n\n## Missing Models ({$missingCount})\n\n";
        $report .= "The following database tables lack corresponding Laravel models:\n\n";
        $report .= "| # | Table Name | Expected Model | Suggested Path |\n";
        $report .= "|---|------------|----------------|----------------|\n";

        $counter = 1;
        foreach ($this->missingModels as $table => $modelName) {
            $category = $this->categorizeTable($table);
            $path = "app/Models/{$category}/{$modelName}.php";
            $report .= "| {$counter} | `{$table}` | `{$modelName}` | `{$path}` |\n";
            $counter++;
        }

        // Existing Models Section
        $report .= "\n---\n\n## Existing Models ({$existingCount})\n\n";
        $report .= "| # | Table Name | Model Class | File Path |\n";
        $report .= "|---|------------|-------------|----------|\n";

        $counter = 1;
        foreach ($this->existingModels as $table => $info) {
            $report .= "| {$counter} | `{$table}` | `{$info['class']}` | `{$info['path']}` |\n";
            $counter++;
        }

        // Priority Recommendations
        $report .= "\n---\n\n## Priority Recommendations\n\n";
        $report .= $this->generatePriorityRecommendations();

        // Categorized Missing Models
        $report .= "\n---\n\n## Missing Models by Category\n\n";
        $report .= $this->generateCategorizedMissing();

        // Save report
        File::ensureDirectoryExists(dirname(base_path($outputPath)));
        File::put(base_path($outputPath), $report);
    }

    protected function generateJsonReport(string $outputPath): void
    {
        $report = [
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'total_tables' => count($this->tables),
                'models_exist' => count($this->existingModels),
                'models_missing' => count($this->missingModels),
                'coverage_percentage' => count($this->tables) > 0
                    ? round((count($this->existingModels) / count($this->tables)) * 100, 1)
                    : 0,
            ],
            'missing_models' => array_map(function ($table) use (&$modelName) {
                return [
                    'table' => $table,
                    'expected_model' => $this->missingModels[$table],
                    'category' => $this->categorizeTable($table),
                ];
            }, array_keys($this->missingModels)),
            'existing_models' => array_map(function ($table) {
                return [
                    'table' => $table,
                    'model_class' => $this->existingModels[$table]['class'],
                    'path' => $this->existingModels[$table]['path'],
                ];
            }, array_keys($this->existingModels)),
        ];

        File::ensureDirectoryExists(dirname(base_path($outputPath)));
        File::put(base_path($outputPath), json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function categorizeTable(string $table): string
    {
        $categories = [
            'Security' => ['permission', 'role', 'session', 'audit'],
            'Core' => ['org', 'user', 'settings'],
            'Context' => ['context', 'field_definition', 'field_value'],
            'Campaign' => ['campaign'],
            'Creative' => ['creative', 'content', 'copy', 'brief'],
            'AdPlatform' => ['ad_', 'meta_', 'tiktok_', 'twitter_'],
            'Social' => ['social_', 'post_', 'channel_'],
            'Knowledge' => ['knowledge_', 'embedding', 'semantic'],
            'AI' => ['ai_', 'cognitive', 'model_'],
            'Analytics' => ['metric', 'kpi', 'analytics'],
            'Operations' => ['ops_', 'etl_', 'sync_', 'log'],
        ];

        foreach ($categories as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (Str::contains($table, $pattern)) {
                    return $category;
                }
            }
        }

        return 'Other';
    }

    protected function generatePriorityRecommendations(): string
    {
        $recommendations = "### Critical Priority (Security & Core)\n\n";
        $recommendations .= "These models are essential for system security and basic functionality:\n\n";

        $critical = ['permissions_cache', 'session_context', 'audit_log'];
        foreach ($critical as $table) {
            if (isset($this->missingModels[$table])) {
                $recommendations .= "- ⚠️ **`{$table}`** → `{$this->missingModels[$table]}`\n";
            }
        }

        $recommendations .= "\n### High Priority (Business Logic)\n\n";
        $recommendations .= "These models are required for core business features:\n\n";

        $high = [];
        foreach ($this->missingModels as $table => $model) {
            $category = $this->categorizeTable($table);
            if (in_array($category, ['Campaign', 'Creative', 'Knowledge'])) {
                $high[$table] = $model;
            }
        }

        foreach (array_slice($high, 0, 10) as $table => $model) {
            $recommendations .= "- 🔸 **`{$table}`** → `{$model}`\n";
        }

        return $recommendations;
    }

    protected function generateCategorizedMissing(): string
    {
        $categorized = [];
        foreach ($this->missingModels as $table => $model) {
            $category = $this->categorizeTable($table);
            $categorized[$category][] = ['table' => $table, 'model' => $model];
        }

        ksort($categorized);

        $output = "";
        foreach ($categorized as $category => $items) {
            $count = count($items);
            $output .= "### {$category} ({$count})\n\n";
            foreach ($items as $item) {
                $output .= "- `{$item['table']}` → `{$item['model']}`\n";
            }
            $output .= "\n";
        }

        return $output;
    }
}
