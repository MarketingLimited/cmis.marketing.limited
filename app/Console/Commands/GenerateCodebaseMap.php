<?php

namespace App\Console\Commands;

use App\Jobs\Knowledge\DiscoverCodebaseMap;
use Illuminate\Console\Command;

class GenerateCodebaseMap extends Command
{
    protected $signature = 'knowledge:generate-codebase-map';

    protected $description = 'Generate auto-updated codebase map including models, controllers, services, and relationships';

    public function handle(): int
    {
        $this->info('🔍 Discovering codebase structure...');

        try {
            $job = new DiscoverCodebaseMap();
            $job->handle();

            $outputPath = base_path('.claude/knowledge/auto-generated/CODEBASE_MAP.md');
            $this->info("✅ Codebase map generated: {$outputPath}");

            if (file_exists($outputPath)) {
                $size = round(filesize($outputPath) / 1024, 2);
                $this->comment("   File size: {$size} KB");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to generate codebase map: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
