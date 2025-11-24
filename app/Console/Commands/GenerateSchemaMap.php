<?php

namespace App\Console\Commands;

use App\Jobs\Knowledge\DiscoverDatabaseSchema;
use Illuminate\Console\Command;

class GenerateSchemaMap extends Command
{
    protected $signature = 'knowledge:generate-schema-map';

    protected $description = 'Generate auto-updated database schema map including tables, RLS policies, and relationships';

    public function handle(): int
    {
        $this->info('🔍 Discovering database schema...');

        try {
            $job = new DiscoverDatabaseSchema();
            $job->handle();

            $outputPath = base_path('.claude/knowledge/auto-generated/DATABASE_SCHEMA_MAP.md');
            $this->info("✅ Schema map generated: {$outputPath}");

            if (file_exists($outputPath)) {
                $size = round(filesize($outputPath) / 1024, 2);
                $this->comment("   File size: {$size} KB");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to generate schema map: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
