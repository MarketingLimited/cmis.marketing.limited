<?php

namespace App\Console\Commands;

use App\Jobs\Knowledge\DiscoverServiceConnections;
use Illuminate\Console\Command;

class GenerateServiceMap extends Command
{
    protected $signature = 'knowledge:generate-service-map';

    protected $description = 'Generate auto-updated service layer map showing Controller → Service → Repository flows';

    public function handle(): int
    {
        $this->info('🔍 Discovering service layer connections...');

        try {
            $job = new DiscoverServiceConnections();
            $job->handle();

            $outputPath = base_path('.claude/knowledge/auto-generated/SERVICE_LAYER_MAP.md');
            $this->info("✅ Service map generated: {$outputPath}");

            if (file_exists($outputPath)) {
                $size = round(filesize($outputPath) / 1024, 2);
                $this->comment("   File size: {$size} KB");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to generate service map: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
