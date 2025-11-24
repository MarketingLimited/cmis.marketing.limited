<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class KnowledgeHealthCheck extends Command
{
    protected $signature = 'knowledge:health-check';

    protected $description = 'Check health and freshness of auto-generated knowledge files';

    protected array $requiredFiles = [
        'DOCS_INDEX.md',              // PRIMARY SOURCE - Check first!
        'CODEBASE_MAP.md',
        'DATABASE_SCHEMA_MAP.md',
        'MODEL_RELATIONSHIP_GRAPH.md',
        'SERVICE_LAYER_MAP.md',
    ];

    public function handle(): int
    {
        $this->info('🏥 Running knowledge health check...');
        $this->newLine();

        $outputDir = base_path('.claude/knowledge/auto-generated');
        $issues = 0;

        // Check if directory exists
        if (!File::isDirectory($outputDir)) {
            $this->error("❌ Knowledge directory does not exist: {$outputDir}");
            $this->comment("   Run: php artisan knowledge:refresh-all");
            return Command::FAILURE;
        }

        // Check each required file
        foreach ($this->requiredFiles as $filename) {
            $filePath = $outputDir . '/' . $filename;

            if (!File::exists($filePath)) {
                $this->error("❌ Missing: {$filename}");
                $this->comment("   Run: php artisan knowledge:generate-" . $this->getCommandForFile($filename));
                $issues++;
                continue;
            }

            // Check file size
            $size = File::size($filePath);
            if ($size < 1024) { // Less than 1 KB is suspicious
                $this->warn("⚠️  {$filename} is very small ({$size} bytes)");
                $issues++;
            } else {
                $sizeKB = round($size / 1024, 2);
                $this->info("✅ {$filename} ({$sizeKB} KB)");
            }

            // Check freshness (warn if older than 24 hours)
            $lastModified = File::lastModified($filePath);
            $ageHours = round((time() - $lastModified) / 3600, 1);

            if ($ageHours > 24) {
                $this->comment("   ⏰ Stale: {$ageHours} hours old (consider refreshing)");
            } else {
                $this->comment("   ⏰ Fresh: {$ageHours} hours old");
            }

            // Validate content structure
            $content = File::get($filePath);
            if (!str_contains($content, '# Auto-Generated')) {
                $this->warn("   ⚠️  Missing auto-generated header");
                $issues++;
            }

            if (!str_contains($content, 'Last Updated:')) {
                $this->warn("   ⚠️  Missing timestamp");
                $issues++;
            }

            $this->newLine();
        }

        // Summary
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($issues === 0) {
            $this->info('🎉 All knowledge files are healthy!');
        } else {
            $this->warn("⚠️  Found {$issues} issue(s). Consider running:");
            $this->comment('   php artisan knowledge:refresh-all');
        }

        $this->newLine();

        // Coverage stats
        $this->displayCoverageStats();

        return $issues === 0 ? Command::SUCCESS : Command::WARNING;
    }

    protected function getCommandForFile(string $filename): string
    {
        $mapping = [
            'DOCS_INDEX.md' => 'docs-index',
            'CODEBASE_MAP.md' => 'codebase-map',
            'DATABASE_SCHEMA_MAP.md' => 'schema-map',
            'MODEL_RELATIONSHIP_GRAPH.md' => 'model-graph',
            'SERVICE_LAYER_MAP.md' => 'service-map',
        ];

        return $mapping[$filename] ?? 'unknown';
    }

    protected function displayCoverageStats(): void
    {
        $this->comment('📊 Coverage Statistics:');
        $this->newLine();

        // Models
        $modelFiles = File::allFiles(app_path('Models'));
        $totalModels = count($modelFiles);
        $this->line("   Models in codebase: {$totalModels}");

        // Services
        if (File::isDirectory(app_path('Services'))) {
            $serviceFiles = File::allFiles(app_path('Services'));
            $totalServices = count($serviceFiles);
            $this->line("   Services in codebase: {$totalServices}");
        }

        // Controllers
        $controllerFiles = File::allFiles(app_path('Http/Controllers'));
        $totalControllers = count($controllerFiles);
        $this->line("   Controllers in codebase: {$totalControllers}");

        // Database tables
        try {
            $tableCount = \DB::scalar("
                SELECT COUNT(*)
                FROM information_schema.tables
                WHERE table_schema LIKE 'cmis%'
            ");
            $this->line("   Database tables: {$tableCount}");
        } catch (\Exception $e) {
            $this->line("   Database tables: Unable to query");
        }

        $this->newLine();
    }
}
