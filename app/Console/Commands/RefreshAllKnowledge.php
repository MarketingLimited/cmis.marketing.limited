<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshAllKnowledge extends Command
{
    protected $signature = 'knowledge:refresh-all';

    protected $description = 'Refresh all auto-generated knowledge files (codebase, schema, models, services)';

    public function handle(): int
    {
        $this->info('🔄 Refreshing all knowledge maps...');
        $this->newLine();

        $commands = [
            'knowledge:generate-docs-index',     // PRIMARY SOURCE - Always first!
            'knowledge:generate-codebase-map',
            'knowledge:generate-schema-map',
            'knowledge:generate-model-graph',
            'knowledge:generate-service-map',
        ];

        $startTime = microtime(true);
        $failed = false;

        foreach ($commands as $command) {
            try {
                $result = Artisan::call($command);

                $commandName = str_replace('knowledge:generate-', '', $command);
                $this->info("   ✅ {$commandName} completed");

                if ($result !== 0) {
                    $failed = true;
                }
            } catch (\Exception $e) {
                $this->error("   ❌ {$command} failed: {$e->getMessage()}");
                $failed = true;
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        $this->newLine();

        if ($failed) {
            $this->warn("⚠️  Knowledge refresh completed with errors ({$duration}s)");
        } else {
            $this->info("✅ All knowledge maps refreshed successfully ({$duration}s)");
        }

        // Show generated files
        $this->newLine();
        $this->comment('Generated files:');
        $outputDir = base_path('.claude/knowledge/auto-generated');

        if (is_dir($outputDir)) {
            $files = glob($outputDir . '/*.md');
            foreach ($files as $file) {
                $size = round(filesize($file) / 1024, 2);
                $filename = basename($file);
                $this->line("   - {$filename} ({$size} KB)");
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
